/* groovylint-disable CompileStatic */
// CatatanCakadi — test, build, package, and blue/green deploy.
//
// Image transport follows the lombacv pipeline (~/projects/nodejs/catatancakadi),
// already proven on this exact server: `docker save | gzip` -> `scp` ->
// `docker load`, no registry. Production secrets follow the Batamtix pattern
// (~/projects/php/batamtix): the full .env is a single Jenkins "Secret file"
// credential, never committed.
//
// Nginx is NOT touched by this pipeline. cakadi.web.id is currently served by
// a different app (the Nuxt "lombacv" CV site) on this same host, and cutting
// it over is a deliberate manual step — see scripts/deploy-bluegreen.sh's
// header. This pipeline only ships catatancakadi to its own path/ports and
// runs that script, which itself refuses to touch the shared Nginx vhost
// until that manual step has happened once.
//
// ---------------------------------------------------------------------------
// Required Jenkins credentials
// ---------------------------------------------------------------------------
//   catatancakadi-deploy-ssh   SSH Username with private key, for the
//                              deploy host below. MUST BE CREATED.
//   catatancakadi-env          Secret file: the full production .env
//                              (APP_KEY, DB_*, MAIL_*, socialite secrets...).
//                              MUST BE CREATED.

void withDeploySsh(Closure body) {
  withCredentials([
    sshUserPrivateKey(credentialsId: env.DEPLOY_SSH_CRED_ID, keyFileVariable: 'SSH_KEY')
  ]) {
    body()
  }
}

// Shared SSH options, redeclared inside each sh block since Jenkins sh steps
// don't share shell state with each other.
String sshOptsSnippet() {
  return "SSH_OPTS=(-o Port=${env.DEPLOY_SSH_PORT} -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 -o ConnectionAttempts=3 -o ServerAliveInterval=15 -o ServerAliveCountMax=3 -i \"\$SSH_KEY\")"
}

void deployImage() {
  withCredentials([
    file(credentialsId: env.ENV_FILE_CRED_ID, variable: 'ENV_FILE'),
    sshUserPrivateKey(credentialsId: env.DEPLOY_SSH_CRED_ID, keyFileVariable: 'SSH_KEY')
  ]) {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      ${sshOptsSnippet()}

      scp_retry() {
        local src="\$1" dest="\$2" attempt delay
        local delays=(5 15 30 60)
        for attempt in 1 2 3 4 5; do
          if scp "\${SSH_OPTS[@]}" "\$src" "\$dest"; then
            return 0
          fi
          if [ "\$attempt" -eq 5 ]; then
            break
          fi
          delay="\${delays[\$((attempt - 1))]}"
          echo "scp failed (attempt \$attempt/5) for \$src -> \$dest, retrying in \${delay}s..." >&2
          sleep "\$delay"
        done
        echo "scp failed after 5 attempts for \$src -> \$dest" >&2
        return 1
      }

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} "mkdir -p '${DEPLOY_PATH}'"

      scp_retry ${IMAGE_ARCHIVE} ${DEPLOY_HOST}:${DEPLOY_PATH}/${IMAGE_ARCHIVE}
      scp_retry "\$ENV_FILE" ${DEPLOY_HOST}:${DEPLOY_PATH}/.env
      scp_retry docker-compose.prod.yml ${DEPLOY_HOST}:${DEPLOY_PATH}/docker-compose.prod.yml
      scp_retry scripts/deploy-bluegreen.sh ${DEPLOY_HOST}:${DEPLOY_PATH}/deploy-bluegreen.sh
      scp_retry deploy/nginx/cakadi.web.id.conf ${DEPLOY_HOST}:${DEPLOY_PATH}/cakadi.web.id.conf.reference

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} bash -c '
        set -e
        cd ${DEPLOY_PATH}
        gunzip -c ${IMAGE_ARCHIVE} | docker load
        chmod +x deploy-bluegreen.sh
        ./deploy-bluegreen.sh ${DEPLOY_PATH} deploy
        rm -f ${IMAGE_ARCHIVE}
        docker image prune -f
      '
    """
  }
}

// Curls the newly deployed (not-yet-live) colour directly on its port, on
// the deploy host itself — that port isn't reachable from the Jenkins agent,
// only from 127.0.0.1 on the server. Reads which colour/port is pending from
// the state deploy-bluegreen.sh left behind in DEPLOY_PATH/.pending_color.
void smokeTest() {
  withDeploySsh {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      ${sshOptsSnippet()}

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} bash -s <<'REMOTE'
        set -euo pipefail
        cd ${DEPLOY_PATH}

        color="\$(cat .pending_color)"
        case "\$color" in
          blue)  port=5020 ;;
          green) port=5021 ;;
          *) echo "!! Unknown pending color: \$color" >&2; exit 1 ;;
        esac

        echo "==> Smoke-testing \$color on port \$port"
        curl -fsS "http://127.0.0.1:\${port}/up"

        login_status="\$(curl -sS -o /dev/null -w '%{http_code}' "http://127.0.0.1:\${port}/login")"
        if ! echo "\$login_status" | grep -qE '^(200|302)\$'; then
          echo "!! /login returned \$login_status, dumping catatancakadi-\$color logs:" >&2
          docker compose -f docker-compose.prod.yml logs --tail=100 "catatancakadi-\$color" >&2
          echo "!! storage/logs/laravel.log tail:" >&2
          docker compose -f docker-compose.prod.yml exec -T "catatancakadi-\$color" tail -n 100 storage/logs/laravel.log >&2 || echo "   (no laravel.log found)" >&2
          exit 1
        fi
REMOTE
    """
  }
}

// DESTRUCTIVE: drops every table on the newly deployed (not-yet-live) colour
// and rebuilds the schema from scratch via `migrate:fresh --seed`. Runs
// before the smoke test, against the same not-yet-public container/port
// smokeTest() checks, so the live colour is never touched by this step.
//
// AppServiceProvider prohibits migrate:fresh in production on purpose, so
// this calls `app:deploy-migrate-fresh` — the one narrowly-named command
// that opts back out of that guard for exactly this deploy step. Plain
// `migrate:fresh` stays blocked everywhere else, including a shell on this
// same container.
void migrateFresh() {
  withDeploySsh {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      ${sshOptsSnippet()}

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} bash -s <<'REMOTE'
        set -euo pipefail
        cd ${DEPLOY_PATH}

        color="\$(cat .pending_color)"
        echo "==> Running app:deploy-migrate-fresh --seed on catatancakadi-\$color"
        docker compose -f docker-compose.prod.yml exec -T "catatancakadi-\$color" php artisan app:deploy-migrate-fresh --seed
REMOTE
    """
  }
}

void rotate() {
  withDeploySsh {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      ${sshOptsSnippet()}

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} "cd '${DEPLOY_PATH}' && ./deploy-bluegreen.sh '${DEPLOY_PATH}' rotate"
    """
  }
}

void rollback() {
  withDeploySsh {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      ${sshOptsSnippet()}

      ssh "\${SSH_OPTS[@]}" ${DEPLOY_HOST} "cd '${DEPLOY_PATH}' && ./deploy-bluegreen.sh '${DEPLOY_PATH}' rollback"
    """
  }
}

pipeline {
  agent any

  options {
    disableConcurrentBuilds()
    timestamps()
    buildDiscarder(logRotator(numToKeepStr: '10'))
  }

  parameters {
    booleanParam(
      name: 'RUN_TESTS',
      defaultValue: true,
      description: 'Run the Pest suite before building the production image.'
    )
    booleanParam(
      name: 'DEPLOY',
      defaultValue: true,
      description: 'Uncheck to build (and test) without shipping to the server.'
    )
    booleanParam(
      name: 'SHOULD_MIGRATE_FRESH',
      defaultValue: false,
      description: 'DESTRUCTIVE. Runs `migrate:fresh --seed` on the newly deployed colour before the smoke test, dropping and recreating every production table. Also triggered automatically when the head commit message contains `refresh-db`. Leave unchecked unless you explicitly intend to wipe production data.'
    )
  }

  environment {
    // Dockerfile uses RUN --mount=type=cache, which needs BuildKit.
    DOCKER_BUILDKIT = '1'

    IMAGE_NAME    = 'catatancakadi'
    IMAGE_TAG     = 'latest'
    IMAGE_ARCHIVE = "${IMAGE_NAME}-${env.BUILD_NUMBER}.tar.gz"

    // Kredensial SSH (Jenkins: "SSH Username with private key")
    DEPLOY_SSH_CRED_ID = 'catatancakadi-deploy-ssh'
    // Kredensial file .env produksi (Jenkins: "Secret file")
    ENV_FILE_CRED_ID = 'catatancakadi-env'
    // Port SSH server tujuan (dipakai ssh dan scp lewat -o Port=)
    DEPLOY_SSH_PORT = '22026'
    // Server tujuan — sama dengan lombacv/vettrak (satu VPS)
    DEPLOY_HOST = 'root@103.235.72.17'
    // Folder BARU di server, terpisah dari /www/lombacv, sampai cutover
    // manual (lihat header scripts/deploy-bluegreen.sh) dilakukan.
    DEPLOY_PATH = '/www/dk_project/cakadi.web.id'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        script {
          // A `refresh-db` token in the head commit message opts this build
          // into migrate:fresh, same as ticking SHOULD_MIGRATE_FRESH.
          String commitMessage = sh(script: 'git log -1 --pretty=%B', returnStdout: true).trim()
          env.RUN_MIGRATE_FRESH = (params.SHOULD_MIGRATE_FRESH || commitMessage.contains('refresh-db')) ? 'true' : 'false'
          echo "migrate:fresh on this build: ${env.RUN_MIGRATE_FRESH}"
        }
      }
    }

    // Tests and the production build run side by side. All three share the
    // same BuildKit cache and in-flight identical layers (base, vendor,
    // frontend-source) are built once, so the image build is no longer queued
    // behind the tests. Nothing ships unless every branch is green: the
    // archive/deploy stages below only start after this block completes.
    //
    // Pest runs inside the Dockerfile's `testing` target, on the same Alpine
    // PHP the production image ships, from the same lockfile — a green run is
    // a statement about the runtime that ships, not a build agent's PHP.
    // Vitest runs on frontend-source, which branches before the production
    // bundle build, so it never waits on `build:ssr`.
    stage('Verify & Build') {
      failFast true
      parallel {
        stage('Pest') {
          when { expression { params.RUN_TESTS } }
          steps {
            sh '''
              set -eu
              docker build --target testing --tag "${IMAGE_NAME}:testing-${BUILD_NUMBER}" .
              docker image rm -f "${IMAGE_NAME}:testing-${BUILD_NUMBER}" >/dev/null 2>&1 || true
            '''
          }
        }
        stage('Vitest') {
          when { expression { params.RUN_TESTS } }
          steps {
            sh '''
              set -eu
              docker build --target frontend-testing --tag "${IMAGE_NAME}:frontend-testing-${BUILD_NUMBER}" .
              docker image rm -f "${IMAGE_NAME}:frontend-testing-${BUILD_NUMBER}" >/dev/null 2>&1 || true
            '''
          }
        }
        stage('Build image') {
          steps {
            sh 'docker build --pull --tag ${IMAGE_NAME}:${IMAGE_TAG} .'
          }
        }
      }
    }

    stage('Save image to archive') {
      when { expression { params.DEPLOY } }
      steps {
        sh 'docker save ${IMAGE_NAME}:${IMAGE_TAG} | gzip > ${IMAGE_ARCHIVE}'
      }
    }

    stage('Ship & deploy') {
      when { expression { params.DEPLOY } }
      steps {
        script {
          deployImage()
        }
      }
    }

    // Opt-in and destructive: wipes and recreates the schema on the new
    // colour, before it's smoke tested and before it's ever live.
    stage('Migrate Fresh') {
      when { expression { params.DEPLOY && env.RUN_MIGRATE_FRESH == 'true' } }
      steps {
        script {
          migrateFresh()
        }
      }
    }

    // Hits the new colour directly on its own port (still off the live
    // vhost) before anything real sees it. A failure here rolls the new
    // colour back and leaves the currently-live colour untouched.
    stage('Smoke Test') {
      when { expression { params.DEPLOY } }
      steps {
        script {
          try {
            smokeTest()
          } catch (err) {
            echo "Smoke test failed, rolling back the new color."
            rollback()
            error("Smoke test failed: ${err}")
          }
        }
      }
    }

    // Only after the smoke test passes: flip Nginx to the new colour and
    // stop the old one. See scripts/deploy-bluegreen.sh's header for the
    // one-time manual cutover this depends on.
    stage('Rotate') {
      when { expression { params.DEPLOY } }
      steps {
        script {
          rotate()
        }
      }
    }
  }

  post {
    always {
      sh 'rm -f ${IMAGE_ARCHIVE}'
      sh 'docker image rm ${IMAGE_NAME}:${IMAGE_TAG} || true'
    }
  }
}
