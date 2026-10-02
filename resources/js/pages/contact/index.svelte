<script lang="ts">
  import Icon from '@iconify/svelte';
  import Info from '@lucide/svelte/icons/info';
  import Send from '@lucide/svelte/icons/send';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { store } from '@/wayfinder/routes/contact';

  type ContactItem = {
    label: string;
    value: string;
    url: string | null;
    note: string | null;
  };

  let {
    information,
    socials,
    reasons,
  }: {
    information: ContactItem[];
    socials: ContactItem[];
    reasons: { value: string; label: string }[];
  } = $props();

  let formKey = $state(0);
</script>

<AppHead title="Hubungi Saya" />

<div id="contact-page">
  <HeaderPage
    title="Hubungi Saya"
    subtitle="Berikut kontak yang dapat dihubungi apabila anda tertarik dengan skill saya maupun ingin bekerjasama dengan saya."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row flex-lg-row flex-column-reverse">
        <div class="col-md-8">
          <div class="mb-3">
            <div class="alert bg-warning-subtle d-flex gap-3">
              <Info size={32} class="flex-shrink-0" />
              <div class="content">
                Sebagai peringatan keras! Informasi yang saya berikan dibawah ini hanya untuk
                <strong>Kebutuhan Bisnis Saja</strong>. Selebihnya jika kamu ingin terhubung, bisa langsung
                kirimkan pesan ke sosial media saya dengan syarat harus sopan dan memperkenalkan diri dan
                dari instansi, institusi, perusahaan dan atau daerah mana. Insyaallah saya akan menjawab
                secepatnya.
              </div>
            </div>
            <p>Saya sangat menghargai kamu apabila menggunakan fitur ini dengan penuh tanggungjawab.</p>
          </div>

          <div class="mt-4 border-top pt-4" id="information">
            <h4 class="mb-3">Informasi Kontak</h4>

            <div class="card mb-4 overflow-hidden">
              <div class="table-responsive">
                <table class="table mb-0 table-padded">
                  <thead>
                    <tr>
                      <th class="w-25 text-nowrap">Kontak</th>
                      <th>Detail</th>
                    </tr>
                  </thead>
                  <tbody>
                    {#each information as item (item.label)}
                      <tr>
                        <td class="w-25 text-nowrap">{item.label}</td>
                        <td>
                          {#if item.url}
                            <a href={item.url} target="_blank" rel="noopener">{item.value}</a>
                          {:else}
                            {item.value}
                          {/if}
                          {#if item.note}
                            <span title={item.note}>
                              <Info size={14} />
                            </span>
                          {/if}
                        </td>
                      </tr>
                    {:else}
                      <tr>
                        <td colspan="2" class="text-center opacity-75">Belum ada informasi kontak.</td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>

            <p>
              Jika Anda membutuhkan informasi lebih lanjut, seperti nomor telepon saya, jangan ragu untuk
              mengirimkan email kepada saya terlebih dahulu.
            </p>
          </div>

          <div class="mt-4 border-top pt-4" id="socmed">
            <h4 class="mb-3">Sosial Media</h4>
            <p>
              Kalau kamu mau cari semua sosial mediaku, kamu bisa menggunakan pencarian
              <strong>@cakadi190 / @cakadi.id</strong> yang mana itu adalah nama pengguna di hampir semua
              sosialku.
            </p>
            <p>Tapi mungkin kamu gak punya waktu untuk nyari sosmedku, bisa deh pake daftar tautan dibawah ini.</p>
            <div class="card mb-4 overflow-hidden">
              <div class="table-responsive">
                <table class="table mb-0 table-padded">
                  <thead>
                    <tr>
                      <th class="w-25 text-nowrap">Platform</th>
                      <th>Detail</th>
                    </tr>
                  </thead>
                  <tbody>
                    {#each socials as link (link.label)}
                      <tr>
                        <td class="w-25 text-nowrap">{link.label}</td>
                        <td>
                          {#if link.url}
                            <a href={link.url} target="_blank" rel="noopener" class="text-decoration-none d-inline-flex align-items-center gap-1">
                              {link.value}
                              <Icon icon="lucide:external-link" width={14} height={14} />
                            </a>
                          {:else}
                            {link.value}
                          {/if}
                        </td>
                      </tr>
                    {:else}
                      <tr>
                        <td colspan="2" class="text-center opacity-75">Belum ada sosial media.</td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="border-top pt-4 mt-4" id="form-contact">
            <h3>Atau Kirim Pesan Melalui Form Ini</h3>
            <p class="opacity-75">
              Gunakan formulir dibawah ini dan terhubung dengan saya supaya lebih kenal, akrab dan bisa
              dapet proyek dadakan dari saya. Mohon jangan kirimkan pesan lebih dari 5x, karena bisa
              membebani <em>server</em>!
            </p>

            {#key formKey}
              <Form
                {...store.form()}
                class="pt-1 d-flex flex-column gap-3"
                novalidate
                resetOnSuccess
                onSuccess={() => formKey++}
              >
                {#snippet children({ errors, processing })}
                  <div class="row g-3">
                    <Field.Group class="col-md-6">
                      <Field.Label for="fullName">Nama Lengkapmu</Field.Label>
                      <Field.Input id="fullName" name="name" placeholder="Mis: Cak Adi" invalid={!!errors.name} />
                      <Field.Feedback message={errors.name} />
                    </Field.Group>
                    <Field.Group class="col-md-6">
                      <Field.Label for="contactEmail">Surel (atau <em>E-Mail</em>)</Field.Label>
                      <Field.Input
                        id="contactEmail"
                        name="email"
                        type="email"
                        placeholder="Mis: cakadi@email.com"
                        invalid={!!errors.email}
                      />
                      <Field.Feedback message={errors.email} />
                    </Field.Group>
                  </div>

                  <Field.Group>
                    <Field.Label for="contactReason">Ada Perlu Apa?</Field.Label>
                    <select id="contactReason" name="reason" class="form-select" class:is-invalid={!!errors.reason}>
                      {#each reasons as reason (reason.value)}
                        <option value={reason.value}>{reason.label}</option>
                      {/each}
                    </select>
                    <Field.Feedback message={errors.reason} />
                  </Field.Group>

                  <Field.Group>
                    <Field.Label>Pesan Anda</Field.Label>
                    <RichTextEditor
                      name="message"
                      minimal
                      placeholder="Tuliskan pesan anda disini…"
                      invalid={!!errors.message}
                    />
                    <Field.Feedback message={errors.message} />
                  </Field.Group>

                  <div>
                    <button type="submit" class="btn btn-primary d-flex gap-2 align-items-center" disabled={processing}>
                      <span>Kirim</span>
                      <Send size={16} />
                    </button>
                  </div>
                {/snippet}
              </Form>
            {/key}
          </div>
        </div>

        <div class="col-md-4">
          <div class="card sticky-top rounded-4 overflow-hidden">
            <div class="card-header p-4">
              <h4 class="mb-0">Navigasi</h4>
            </div>
            <div class="card-body">
              <ul class="nav nav-pills flex-column align-items-stretch">
                <li class="nav-item"><a href="#information" class="nav-link py-3 w-100 text-start">Informasi Kontak</a></li>
                <li class="nav-item"><a href="#socmed" class="nav-link py-3 w-100 text-start">Sosial Media</a></li>
                <li class="nav-item"><a href="#form-contact" class="nav-link py-3 w-100 text-start">Formulir Kontak</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
