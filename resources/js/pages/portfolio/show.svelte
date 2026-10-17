<script lang="ts">
  import { localizeForm, localizeHref, t } from '@/lib/i18n';
  import '../../../css/content.scss';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import Icon from '@iconify/svelte';
  import Expand from '@lucide/svelte/icons/expand';
  import Link2 from '@lucide/svelte/icons/link-2';
  import { technologyIconUrl, type TechnologyChip } from '@/lib/tech-icon';
  import AppHead from '@/components/app-head.svelte';
  import { highlightCode } from '@/lib/highlight/action';
  import HeaderPage from '@/components/header-page.svelte';
  import Lightbox from '@/components/ui/lightbox.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
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
    technologies: TechnologyChip[];
    slug: string;
    imageSrcset?: string | null;
    imageSize?: { width: number; height: number } | null;
    galleries: {
      url: string;
      thumb?: string | null;
      size?: { width: number; height: number } | null;
      title: string | null;
    }[];
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
    backTo="/portfolio"
    title={t('portfolios.show.heading')}
    subtitle={t('portfolios.show.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <img
            src={portfolio.image}
            srcset={portfolio.imageSrcset ?? undefined}
            sizes="(min-width: 1200px) 1140px, 100vw"
            width={portfolio.imageSize?.width}
            height={portfolio.imageSize?.height}
            fetchpriority="high"
            decoding="async"
            class="w-100 h-auto rounded-4 border overflow-hidden"
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
                  <span>{t('portfolios.show.source')}</span>
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
                  <span>{t('portfolios.show.demo')}</span>
                </a>
              {/if}
            </div>
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <div class="mb-5">
                <h2 id="description" class="h3">
                  {t('portfolios.show.description')}
                </h2>
                {#if portfolio.description}
                  <div
                    class="wysiwyg-content-wrapper"
                    use:highlightCode={portfolio.description}
                  >
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    {@html portfolio.description}
                  </div>
                {:else}
                  <p class="opacity-75">
                    {t('portfolios.show.no_description')}
                  </p>
                {/if}
              </div>

              <div class="mb-5">
                <h2 id="techstack" class="h3">
                  {t('portfolios.show.built_with')}
                </h2>

                <div class="d-flex gap-3 align-items-center flex-wrap">
                  {#each portfolio.technologies as tech (tech.name)}
                    <img src={technologyIconUrl(tech)} alt={tech.name} title={tech.name} width="32" height="32" loading="lazy" />
                  {/each}
                </div>
              </div>

              <div class="mb-5" id="gallery">
                <h2 class="h3">{t('portfolios.show.gallery')}</h2>

                {#if portfolio.galleries.length > 0}
                  <div class="row g-3">
                    {#each portfolio.galleries as gallery, position (gallery.url)}
                      <div class="col-6 col-lg-4">
                        <button
                          type="button"
                          class="position-relative d-block w-100 p-0 border rounded-3 overflow-hidden bg-transparent"
                          aria-label={t('portfolios.show.preview', {
                            title:
                              gallery.title ||
                              t('portfolios.show.image_n', { n: position + 1 }),
                          })}
                          onclick={() => openLightbox(position)}
                        >
                          <img
                            src={gallery.thumb ?? gallery.url}
                            width={gallery.size ? 640 : undefined}
                            height={gallery.size
                              ? Math.round(
                                  (640 * gallery.size.height) /
                                    gallery.size.width,
                                )
                              : undefined}
                            alt={gallery.title ??
                              `${portfolio.name} ${position + 1}`}
                            loading="lazy"
                            decoding="async"
                            class="w-100"
                            style="aspect-ratio: 1 / 1; object-fit: cover; object-position: top;"
                          />
                          <span
                            class="position-absolute top-0 end-0 m-2 badge text-bg-dark"
                          >
                            <Expand size={14} />
                          </span>
                          {#if gallery.title}
                            <span
                              class="position-absolute bottom-0 end-0 m-2 badge text-bg-dark text-truncate"
                              style="max-width: calc(100% - 1rem);"
                              >{gallery.title}</span
                            >
                          {/if}
                        </button>
                      </div>
                    {/each}
                  </div>

                  <Lightbox
                    bind:open={lightboxOpen}
                    bind:index={lightboxIndex}
                    images={lightboxImages}
                  />
                {:else}
                  <p class="opacity-75">{t('portfolios.show.no_gallery')}</p>
                {/if}
              </div>

              <div id="rating">
                <h2 class="h3">{t('portfolios.show.rating')}</h2>

                {#if reviewSummary.count > 0}
                  <p class="d-flex align-items-center gap-2 mb-4">
                    <Star size={20} class="text-warning" fill="currentColor" />
                    <strong>{reviewSummary.average}</strong>
                    <span class="opacity-75"
                      >{t('portfolios.show.review_count', {
                        count: reviewSummary.count,
                      })}</span
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
                              aria-label={t('portfolios.show.out_of_five', {
                                rating: review.rating,
                              })}
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
                          <h3 class="h6">{review.title}</h3>
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
                    {t('portfolios.show.no_reviews')}
                  </p>
                {/if}

                <div class="card rounded-4">
                  <div class="card-body p-4">
                    <h3 class="h5">{t('portfolios.review.title')}</h3>
                    <p class="opacity-75">
                      {@html t('portfolios.review.required')}
                    </p>

                    {#key formKey}
                      <Form
                        {...localizeForm(store.form(portfolio.slug))}
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
                                >{t('portfolios.review.name')}
                                <span class="text-danger">*</span></Field.Label
                              >
                              <Field.Input
                                id="review-name"
                                name="reviewer_name"
                                placeholder={t(
                                  'portfolios.review.name_placeholder',
                                )}
                                invalid={!!errors.reviewer_name}
                              />
                              <Field.Feedback message={errors.reviewer_name} />
                            </Field.Group>
                            <Field.Group class="col-md-6">
                              <Field.Label for="review-email"
                                >{t('form.email')}
                                <span class="text-danger">*</span></Field.Label
                              >
                              <Field.Input
                                id="review-email"
                                name="reviewer_email"
                                type="email"
                                placeholder={t(
                                  'portfolios.review.email_placeholder',
                                )}
                                invalid={!!errors.reviewer_email}
                              />
                              <Field.Feedback message={errors.reviewer_email} />
                            </Field.Group>
                          </div>

                          <Field.Group>
                            <Field.Label for="review-company"
                              >{t('portfolios.review.company')}</Field.Label
                            >
                            <Field.Input
                              id="review-company"
                              name="reviewer_company"
                              placeholder={t('portfolios.review.optional')}
                              invalid={!!errors.reviewer_company}
                            />
                            <Field.Feedback message={errors.reviewer_company} />
                          </Field.Group>

                          <Field.Group>
                            <Field.Label for="review-title"
                              >{t('portfolios.review.review_title')}
                              <span class="text-danger">*</span></Field.Label
                            >
                            <Field.Input
                              id="review-title"
                              name="title"
                              placeholder={t(
                                'portfolios.review.title_placeholder',
                              )}
                              invalid={!!errors.title}
                            />
                            <Field.Feedback message={errors.title} />
                          </Field.Group>

                          <Field.Group>
                            <Field.Label
                              >{t('portfolios.review.rating')}
                              <span class="text-danger">*</span></Field.Label
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
                                  aria-label={t('portfolios.review.stars', {
                                    value,
                                  })}
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
                              >{t('portfolios.review.body')}
                              <span class="text-danger">*</span></Field.Label
                            >
                            <RichTextEditor
                              name="comment"
                              minimal
                              placeholder={t(
                                'portfolios.review.body_placeholder',
                              )}
                              invalid={!!errors.comment}
                            />
                            <Field.Feedback message={errors.comment} />
                          </Field.Group>

                          <CaptchaSubmitRow
                            error={errors['cf-turnstile-response']}
                          >
                            <button
                              type="submit"
                              class="btn btn-primary"
                              disabled={processing}
                              >{t('portfolios.review.submit')}</button
                            >
                          </CaptchaSubmitRow>
                          <p class="small opacity-75 mb-0">
                            {t('portfolios.review.note')}
                            <a href={localizeHref('/disclaimer')}
                              >{t('portfolios.review.policy')}</a
                            >.
                          </p>
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
                  <h2 class="h4 mb-0">{t('portfolios.nav.title')}</h2>
                </div>
                <div class="card-body">
                  <ul class="nav nav-pills flex-column align-items-stretch">
                    <li class="nav-item">
                      <a
                        href="#description"
                        class="nav-link py-3 w-100 text-start"
                        >{t('portfolios.show.description')}</a
                      >
                    </li>
                    <li class="nav-item">
                      <a
                        href="#techstack"
                        class="nav-link py-3 w-100 text-start"
                        >{t('portfolios.show.built_with')}</a
                      >
                    </li>
                    <li class="nav-item">
                      <a href="#gallery" class="nav-link py-3 w-100 text-start"
                        >{t('portfolios.show.gallery_short')}</a
                      >
                    </li>
                    <li class="nav-item">
                      <a href="#rating" class="nav-link py-3 w-100 text-start"
                        >{t('portfolios.show.rating_short')}</a
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
