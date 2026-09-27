<script lang="ts">
  import Icon from '@iconify/svelte';
  import Info from '@lucide/svelte/icons/info';
  import Send from '@lucide/svelte/icons/send';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  const email = 'me@cakadi.id';
  const altEmail = 'cakadi190@gmail.com';
  const phone = '081234771365';
  const website = 'https://www.cakadi.web.id';

  const socialMediaLinks = [
    { platform: 'Facebook', url: 'https://www.facebook.com/cakadi190' },
    { platform: 'Instagram', url: 'https://www.instagram.com/masadi.dev/' },
    { platform: 'Twitter', url: 'https://x.com/cakadi190' },
    { platform: 'Youtube', url: 'https://youtube.com/@catatancakadi' },
    { platform: 'LinkedIn', url: 'https://linkedin.com/in/cakadi190' },
    { platform: 'TikTok', url: 'https://tiktok.com/@cakadi190' },
  ];

  function whatsappLink(number: string): string {
    return `https://wa.me/62${number.replace(/^0/, '')}`;
  }
</script>

<AppHead title="Hubungi Saya">
  <meta
    name="description"
    content="Berikut kontak yang dapat dihubungi apabila anda tertarik dengan skill saya maupun ingin bekerjasama dengan saya."
  />
</AppHead>

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
                    <tr>
                      <td class="text-nowrap w-25">Alamat</td>
                      <td>
                        Ngawi, Jawa Timur
                        <span
                          title="Maaf, saya tidak bisa memberikan alamat lengkap rumah saya dengan alasan privasi. Mohon untuk menghargainya!"
                        >
                          <Info size={14} />
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td class="text-nowrap w-25">Zona Waktu</td>
                      <td>GMT+07:00 (Indonesian Western Time / UTC+7)</td>
                    </tr>
                    <tr>
                      <td class="w-25 text-nowrap">Surat Elektronik</td>
                      <td>
                        <div class="d-flex gap-1 flex-wrap align-items-center">
                          <a class="text-decoration-line-through disabled" href={`mailto:${email}`}>{email}</a>
                          <span title="Sementara belum bisa karena hosting saya belum saya perpanjang wkwkwk.">
                            <Info size={14} />
                          </span>
                          <span>/</span>
                          <a href={`mailto:${altEmail}`}>{altEmail}</a>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="w-25 text-nowrap">Whatsapp (hanya chat)</td>
                      <td><a href={whatsappLink(phone)}>{phone}</a></td>
                    </tr>
                    <tr>
                      <td class="w-25 text-nowrap">Website</td>
                      <td><a href={website}>{website}</a></td>
                    </tr>
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
                    {#each socialMediaLinks as link (link.platform)}
                      <tr>
                        <td class="w-25 text-nowrap">{link.platform}</td>
                        <td>
                          <a href={link.url} target="_blank" rel="noopener" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            {link.url}
                            <Icon icon="lucide:external-link" width={14} height={14} />
                          </a>
                        </td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="border-top pt-4 mt-4" id="form-contact">
            <div class="alert bg-info-subtle d-flex gap-3">
              <Info size={32} class="flex-shrink-0" />
              <span>
                Mohon bersabar ya, karena fitur ini masih saya kembangkan untuk kedepannya bisa mengirimkan
                pesan dan menyimpan pesan yang kamu kirimkan.
              </span>
            </div>

            <h3>Atau Kirim Pesan Melalui Form Ini</h3>
            <p class="opacity-75">
              Gunakan formulir dibawah ini dan terhubung dengan saya supaya lebih kenal, akrab dan bisa
              dapet proyek dadakan dari saya. Mohon jangan kirimkan pesan lebih dari 5x, karena bisa
              membebani <em>server</em>!
            </p>

            <form class="pt-1" onsubmit={(event) => event.preventDefault()}>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="fullName" class="input-label">Nama Lengkapmu</label>
                    <input id="fullName" type="text" class="form-control" placeholder="Mis: Cak Adi" />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="contactEmail" class="input-label">Surat Elektronik (atau <em>E-Mail</em>)</label>
                    <input id="contactEmail" type="text" class="form-control" placeholder="Mis: cakadi@email.com" />
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label for="contactReason" class="input-label d-block">Ada Perlu Apa?</label>
                <select id="contactReason" class="form-select">
                  <option value="connecting">Izin Berkoneksi</option>
                  <option value="services" disabled>Layanan (Masih belum tersedia)</option>
                  <option value="project-collabs">Kolaborasi Proyek</option>
                  <option value="general">Ingin Bertanya Hal Umum</option>
                  <option value="others">Hal Lainnya</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="messageText" class="input-label">Pesan Anda</label>
                <textarea id="messageText" class="form-control" rows="4" placeholder="Tuliskan pesan anda disini…"
                ></textarea>
              </div>

              <div>
                <button type="submit" class="btn btn-primary d-flex gap-2 align-items-center">
                  <span>Kirim</span>
                  <Send size={16} />
                </button>
              </div>
            </form>
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
