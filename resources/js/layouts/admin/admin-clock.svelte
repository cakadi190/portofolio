<script lang="ts">
  import ClockIcon from '@lucide/svelte/icons/clock';

  function format(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
  }

  let now = $state(format(new Date()));

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
  aria-label="Open calendar"
>
  <ClockIcon size={16} aria-hidden="true" />
  <span>{now}</span>
</button>

<div
  class="modal fade"
  role="dialog"
  aria-modal="true"
  id="adminClockModal"
  tabindex="-1"
  aria-labelledby="adminClockModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adminClockModalLabel">Calendar</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>
      <div class="modal-body">
        <p>Nothing scheduled.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
          >Close</button
        >
      </div>
    </div>
  </div>
</div>
