<script lang="ts">
    import AppHead from "@/components/app-head.svelte";
</script>

<AppHead title="Welcome" />

<div class="container py-5">
    <h1>Gettix</h1>

    <div class="dropdown">
        <button
            class="btn btn-secondary dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Dropdown button
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
        </ul>
    </div>
</div>
