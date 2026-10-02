<script lang="ts">
  import Icon from '@iconify/svelte';
  import Expand from '@lucide/svelte/icons/expand';
  import Link2 from '@lucide/svelte/icons/link-2';
  import { techIcon } from '@/lib/tech-icon';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import ImageLightbox from '@/components/media/image-lightbox.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { Form } from '@inertiajs/svelte';
  import Star from '@lucide/svelte/icons/star';
  import { store } from '@/wayfinder/routes/portfolios/reviews';

  type Portfolio = {
    name: string;
    shortDesc: string | null;
    description: string | null;
    image: string;
    demoLink: string | null;
    sourceCode: string | null;
    isPrivate: boolean;
    technologies: string[];
    slug: string;
    galleries: { url: string; title: string | null }[];
  };

  type Review = {
    id: number;
    name: string;
    company: string | null;
    title: string;
    rating: number;
    comment: string;
    createdAt: string | null;
  };

  let {
    portfolio,
    reviews,
    reviewSummary,
  }: {
    portfolio: Portfolio;
    reviews: Review[];
    reviewSummary: { average: number; count: number };
  } = $props();

  let selectedRating = $state(0);
  let formKey = $state(0);
  let lightboxOpen = $state(false);
  let lightboxIndex = $state(0);

  const lightboxImages = $derived(
    portfolio.galleries.map((gallery, position) => ({
      url: gallery.url,
      title: gallery.title || `${portfolio.name} - ${position + 1}`,
    })),
  );

  function openLightbox(position: number): void {
    lightboxIndex = position;
    lightboxOpen = true;
  }
</script>

<AppHead title={portfolio.name} />

<div id="project-detail">
  <HeaderPage
    backTo="/portofolio"
    title="Detail Proyek"
    subtitle="Berikut saya tampilkan detail proyek yang saya kerjakan ini."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <img
            src={portfolio.image}
            class="w-100 rounded-4 border overflow-hidden mt-4 mt-md-0"
            alt={portfolio.name}
          />

          <div
            class="pt-5 pb-4 flex-column flex-lg-row border-bottom mb-5 align-items-start align-items-lg-center d-flex justify-content-between gap-3"
          >
            <div>
              <h1 class="h3">{portfolio.name}</h1>
              {#if portfolio.shortDesc}
                <p class="opacity-75 mb-0">{portfolio.shortDesc}</p>
              {/if}
            </div>
            <div class="d-flex flex-shrink-0 gap-3">
              {#if !portfolio.isPrivate && portfolio.sourceCode}
                <a
                  href={portfolio.sourceCode}
                  target="_blank"
                  rel="noopener"
                  class="d-flex gap-2 align-items-center btn btn-outline-primary"
                >
                  <Icon icon="fa6-brands:github" />
                  <span>Source Code</span>
                </a>
              {/if}
              {#if portfolio.demoLink}
                <a
                  href={portfolio.demoLink}
                  target="_blank"
                  rel="noopener"
                  class="d-flex gap-2 align-items-center btn btn-primary"
                >
                  <Link2 size={16} />
                  <span>Live Demo</span>
                </a>
              {/if}
            </div>
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <div class="mb-5">
                <h3 id="description">Deskripsi Proyek</h3>
                {#if portfolio.description}
                  <div class="wysiwyg-content-wrapper">
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    {@html portfolio.description}
                  </div>
                {:else}
                  <p class="opacity-75">Belum ditambahkan deskripsi.</p>
                {/if}
              </div>

              <div class="mb-5">
                <h3 id="techstack">Dibangun Dengan</h3>

                <div class="d-flex gap-3 align-items-center flex-wrap">
                  {#each portfolio.technologies as tech (tech)}
                    <Icon icon={techIcon(tech)} width={32} height={32} />
                  {/each}
                </div>
              </div>

              <div class="mb-5" id="gallery">
                <h3>Galeri dan Pratinjau Proyek</h3>

                {#if portfolio.galleries.length > 0}
                  <div class="row g-3">
                    {#each portfolio.galleries as gallery, position (gallery.url)}
                      <div class="col-6 col-lg-4">
                        <button
                          type="button"
                          class="position-relative d-block w-100 p-0 border rounded-3 overflow-hidden bg-transparent"
                          aria-label={`Pratinjau ${gallery.title || `gambar ${position + 1}`}`}
                          onclick={() => openLightbox(position)}
                        >
                          <img
                            src={gallery.url}
                            alt={gallery.title ??
                              `${portfolio.name} ${position + 1}`}
                            loading="lazy"
                            class="w-100"
                            style="aspect-ratio: 4 / 3; object-fit: cover;"
                          />
                          <span
                            class="position-absolute bottom-0 end-0 m-2 badge text-bg-dark"
                          >
                            <Expand size={14} />
                          </span>
                        </button>
                        {#if gallery.title}
                          <small class="d-block mt-1 opacity-75"
                            >{gallery.title}</small
                          >
                        {/if}
                      </div>
                    {/each}
                  </div>

                  <ImageLightbox
                    bind:open={lightboxOpen}
                    bind:index={lightboxIndex}
                    images={lightboxImages}
                  />
                {:else}
                  <p class="opacity-75">Belum ada galeri untuk proyek ini.</p>
                {/if}
              </div>

              <div id="rating">
                <h3>Penilaian Proyek Ini</h3>

                {#if reviewSummary.count > 0}
                  <p class="d-flex align-items-center gap-2 mb-4">
                    <Star size={20} class="text-warning" fill="currentColor" />
                    <strong>{reviewSummary.average}</strong>
                    <span class="opacity-75"
                      >dari {reviewSummary.count} ulasan</span
                    >
                  </p>

                  <div class="d-flex flex-column gap-3 mb-5">
                    {#each reviews as review (review.id)}
                      <article class="card rounded-4">
                        <div class="card-body">
                          <div
                            class="d-flex justify-content-between flex-wrap gap-2 mb-2"
                          >
                            <div>
                              <strong>{review.name}</strong>
                              {#if review.company}
                                <span class="opacity-75">
                                  · {review.company}</span
                                >
                              {/if}
                            </div>
                            <span
                              class="d-flex text-warning"
                              aria-label={`${review.rating} dari 5`}
                            >
                              {#each [1, 2, 3, 4, 5] as value (value)}
                                <Star
                                  size={16}
                                  fill={value <= review.rating
                                    ? 'currentColor'
                                    : 'none'}
                                />
                              {/each}
                            </span>
                          </div>
                          <h5 class="h6">{review.title}</h5>
                          <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                          <div>{@html review.comment}</div>
                          {#if review.createdAt}
                            <small class="opacity-75">{review.createdAt}</small>
                          {/if}
                        </div>
                      </article>
                    {/each}
                  </div>
                {:else}
                  <p class="opacity-75 mb-4">
                    Belum ada ulasan. Jadilah yang pertama memberi penilaian!
                  </p>
                {/if}

                <div class="card rounded-4">
                  <div class="card-body p-4">
                    <h4 class="h5">Tulis Ulasan</h4>
                    <p class="opacity-75">
                      Semua kolom bertanda <span class="text-danger">*</span> wajib
                      diisi. Ulasan tampil setelah ditinjau.
                    </p>

                    {#key formKey}
                      <Form
                        {...store.form(portfolio.slug)}
                        class="d-flex flex-column gap-3"
                        novalidate
                        resetOnSuccess
                        onSuccess={() => {
                          selectedRating = 0;
                          formKey++;
                        }}
                      >
                        {#snippet children({ errors, processing })}
                          <div class="row g-3">
                            <Field.Group class="col-md-6">
                              <Field.Label for="review-name"
                                >Nama <span class="text-danger">*</span
                                ></Field.Label
                              >
                              <Field.Input
                                id="review-name"
                                name="reviewer_name"
                                placeholder="Nama Anda"
                                invalid={!!errors.reviewer_name}
                              />
                              <Field.Feedback message={errors.reviewer_name} />
                            </Field.Group>
                            <Field.Group class="col-md-6">
                              <Field.Label for="review-email"
                                >Email <span class="text-danger">*</span
                                ></Field.Label
                              >
                              <Field.Input
                                id="review-email"
                                name="reviewer_email"
                                type="email"
                                placeholder="email@contoh.com"
                                invalid={!!errors.reviewer_email}
                              />
                              <Field.Feedback message={errors.reviewer_email} />
                            </Field.Group>
                          </div>

                          <Field.Group>
                            <Field.Label for="review-company"
                              >Perusahaan / Jabatan</Field.Label
                            >
                            <Field.Input
                              id="review-company"
                              name="reviewer_company"
                              placeholder="Opsional"
                              invalid={!!errors.reviewer_company}
                            />
                            <Field.Feedback message={errors.reviewer_company} />
                          </Field.Group>

                          <Field.Group>
                            <Field.Label for="review-title"
                              >Judul Ulasan <span class="text-danger">*</span
                              ></Field.Label
                            >
                            <Field.Input
                              id="review-title"
                              name="title"
                              placeholder="Ringkasan singkat pengalaman Anda"
                              invalid={!!errors.title}
                            />
                            <Field.Feedback message={errors.title} />
                          </Field.Group>

                          <Field.Group>
                            <Field.Label
                              >Penilaian <span class="text-danger">*</span
                              ></Field.Label
                            >
                            <input
                              type="hidden"
                              name="rating"
                              value={selectedRating || ''}
                            />
                            <div class="d-flex text-warning gap-1">
                              {#each [1, 2, 3, 4, 5] as value (value)}
                                <button
                                  type="button"
                                  class="btn btn-link p-0 text-warning"
                                  aria-label={`${value} bintang`}
                                  onclick={() => (selectedRating = value)}
                                >
                                  <Star
                                    size={28}
                                    fill={value <= selectedRating
                                      ? 'currentColor'
                                      : 'none'}
                                  />
                                </button>
                              {/each}
                            </div>
                            <Field.Feedback message={errors.rating} />
                          </Field.Group>

                          <Field.Group>
                            <Field.Label
                              >Isi Ulasan <span class="text-danger">*</span
                              ></Field.Label
                            >
                            <RichTextEditor
                              name="comment"
                              minimal
                              placeholder="Ceritakan pengalaman Anda..."
                              invalid={!!errors.comment}
                            />
                            <Field.Feedback message={errors.comment} />
                          </Field.Group>

                          <div>
                            <button
                              type="submit"
                              class="btn btn-primary"
                              disabled={processing}>Kirim Ulasan</button
                            >
                          </div>
                        {/snippet}
                      </Form>
                    {/key}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card sticky-top rounded-4">
                <div class="card-header p-4">
                  <h4 class="mb-0">Navigasi</h4>
                </div>
                <div class="card-body">
                  <ul class="nav nav-pills flex-column align-items-stretch">
                    <li class="nav-item">
                      <a
                        href="#description"
                        class="nav-link py-3 w-100 text-start"
                        >Deskripsi Proyek</a
                      >
                    </li>
                    <li class="nav-item">
                      <a
                        href="#techstack"
                        class="nav-link py-3 w-100 text-start"
                        >Dibangun Dengan</a
                      >
                    </li>
                    <li class="nav-item">
                      <a href="#gallery" class="nav-link py-3 w-100 text-start"
                        >Galeri</a
                      >
                    </li>
                    <li class="nav-item">
                      <a href="#rating" class="nav-link py-3 w-100 text-start"
                        >Penilaian</a
                      >
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
