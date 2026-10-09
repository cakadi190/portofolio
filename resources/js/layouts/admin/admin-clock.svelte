<script lang="ts">
  import ClockIcon from '@lucide/svelte/icons/clock';
  import { portal } from '@/lib/dom';
  import AdminCalendar from './admin-calendar.svelte';

  function format(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
  }

  let now = $state(format(new Date()));
  let modalOpen = $state(false);

  function trackModal(node: HTMLElement) {
    const open = () => (modalOpen = true);
    const close = () => (modalOpen = false);

    node.addEventListener('show.bs.modal', open);
    node.addEventListener('hidden.bs.modal', close);

    return {
      destroy() {
        node.removeEventListener('show.bs.modal', open);
        node.removeEventListener('hidden.bs.modal', close);
      },
    };
  }

  $effect(() => {
    const id = window.setInterval(() => {
      now = format(new Date());
    }, 1000);

    return () => window.clearInterval(id);
  });
</script>

<button
  type="button"
  class="nav-link nav-clock"
  data-bs-toggle="modal"
  data-bs-target="#adminClockModal"
  aria-label="Buka kalender kegiatan"
>
  <ClockIcon size={16} aria-hidden="true" />
  <span>{now}</span>
</button>

<div
  use:portal
  use:trackModal
  class="modal fade"
  role="dialog"
  aria-modal="true"
  id="adminClockModal"
  tabindex="-1"
  aria-labelledby="adminClockModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adminClockModalLabel">Kalender Kegiatan</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Tutup"
        ></button>
      </div>
      <div class="modal-body">
        <AdminCalendar active={modalOpen} />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
          >Tutup</button
        >
      </div>
    </div>
  </div>
</div>
