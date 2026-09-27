<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
</script>

<AppHead title="Selamat Datang" />

<div class="container py-5">
  <h1>CatatanCakadi</h1>

  <div class="dropdown">
    <button
      class="btn btn-secondary dropdown-toggle"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      Tombol dropdown
    </button>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item" href="#">Aksi</a></li>
      <li><a class="dropdown-item" href="#">Aksi lainnya</a></li>
      <li><a class="dropdown-item" href="#">Sesuatu yang lain di sini</a></li>
    </ul>
  </div>
</div>
