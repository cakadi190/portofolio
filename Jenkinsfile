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

void deployImage() {
  withCredentials([
    file(credentialsId: env.ENV_FILE_CRED_ID, variable: 'ENV_FILE'),
    sshUserPrivateKey(credentialsId: env.DEPLOY_SSH_CRED_ID, keyFileVariable: 'SSH_KEY')
  ]) {
    sh """#!/usr/bin/env bash
      set -euo pipefail

      SSH_OPTS=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 -o ConnectionAttempts=3 -o ServerAliveInterval=15 -o ServerAliveCountMax=3 -i "\$SSH_KEY")

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
        ./deploy-bluegreen.sh ${DEPLOY_PATH}
        rm -f ${IMAGE_ARCHIVE}
        docker image prune -f
      '
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
  }

  environment {
    IMAGE_NAME    = 'catatancakadi'
    IMAGE_TAG     = 'latest'
    IMAGE_ARCHIVE = "${IMAGE_NAME}-${env.BUILD_NUMBER}.tar.gz"

    // Kredensial SSH (Jenkins: "SSH Username with private key")
    DEPLOY_SSH_CRED_ID = 'catatancakadi-deploy-ssh'
    // Kredensial file .env produksi (Jenkins: "Secret file")
    ENV_FILE_CRED_ID = 'catatancakadi-env'
    // Server tujuan — sama dengan lombacv/vettrak (satu VPS)
    DEPLOY_HOST = 'root@103.235.72.17'
    // Folder BARU di server, terpisah dari /www/lombacv, sampai cutover
    // manual (lihat header scripts/deploy-bluegreen.sh) dilakukan.
    DEPLOY_PATH = '/www/catatancakadi'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    // Runs inside the Dockerfile's `testing` target, on the same Alpine PHP
    // the production image ships, from the same lockfile — a green run is a
    // statement about the runtime that ships, not a build agent's PHP.
    stage('Test') {
      when { expression { params.RUN_TESTS } }
      steps {
        sh '''
          set -eu
          docker build --target testing --tag "${IMAGE_NAME}:testing-${BUILD_NUMBER}" .
          docker image rm -f "${IMAGE_NAME}:testing-${BUILD_NUMBER}" >/dev/null 2>&1 || true
        '''
      }
    }

    stage('Build image') {
      steps {
        sh 'docker build --pull --tag ${IMAGE_NAME}:${IMAGE_TAG} .'
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
  }

  post {
    always {
      sh 'rm -f ${IMAGE_ARCHIVE}'
      sh 'docker image rm ${IMAGE_NAME}:${IMAGE_TAG} || true'
    }
  }
}
