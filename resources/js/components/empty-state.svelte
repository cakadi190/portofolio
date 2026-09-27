<script lang="ts">
  let {
    title,
    text,
  }: {
    title: string;
    text: string;
  } = $props();
</script>

<div class="col-md-6 text-center mx-auto py-5">
  <h3>{title}</h3>
  <p class="text-muted mb-0">{text}</p>
</div>
