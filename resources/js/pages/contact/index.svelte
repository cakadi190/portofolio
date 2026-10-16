<script lang="ts">
  import { localizeForm, t } from '@/lib/i18n';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import Icon from '@iconify/svelte';
  import Info from '@lucide/svelte/icons/info';
  import Send from '@lucide/svelte/icons/send';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
  import Select from '@/components/ui/select.svelte';
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

<AppHead title={t('contact.title')} />

<div id="contact-page">
  <HeaderPage
    title={t('contact.title')}
    subtitle={t('contact.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row flex-lg-row flex-column-reverse">
        <div class="col-md-8">
          <div class="mb-3">
            <div class="alert bg-warning-subtle d-flex gap-3">
              <Info size={32} class="flex-shrink-0" />
              <div class="content">{@html t('contact.warning')}</div>
            </div>
            <p>{t('contact.responsible')}</p>
          </div>

          <div class="mt-4 border-top pt-4" id="information">
            <h2 class="h4 mb-3">{t('contact.info.title')}</h2>

            <div class="card mb-4 overflow-hidden">
              <div class="table-responsive">
                <table class="table mb-0 table-padded">
                  <thead>
                    <tr>
                      <th class="w-25 text-nowrap">{t('contact.info.col_contact')}</th>
                      <th>{t('contact.info.col_detail')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {#each information as item (item.label)}
                      <tr>
                        <td class="w-25 text-nowrap">{item.label}</td>
                        <td>
                          {#if item.url}
                            <a href={item.url} target="_blank" rel="noopener"
                              >{item.value}</a
                            >
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
                        <td colspan="2" class="text-center opacity-75"
                          >{t('contact.info.empty')}</td
                        >
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>

            <p>{t('contact.info.more')}</p>
          </div>

          <div class="mt-4 border-top pt-4" id="socmed">
            <h2 class="h4 mb-3">{t('contact.socials.title')}</h2>
            <p>{@html t('contact.socials.search')}</p>
            <p>{t('contact.socials.list')}</p>
            <div class="card mb-4 overflow-hidden">
              <div class="table-responsive">
                <table class="table mb-0 table-padded">
                  <thead>
                    <tr>
                      <th class="w-25 text-nowrap">{t('contact.socials.col_platform')}</th>
                      <th>{t('contact.info.col_detail')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {#each socials as link (link.label)}
                      <tr>
                        <td class="w-25 text-nowrap">{link.label}</td>
                        <td>
                          {#if link.url}
                            <a
                              href={link.url}
                              target="_blank"
                              rel="noopener"
                              class="text-decoration-none d-inline-flex align-items-center gap-1"
                            >
                              {link.value}
                              <Icon
                                icon="lucide:external-link"
                                width={14}
                                height={14}
                              />
                            </a>
                          {:else}
                            {link.value}
                          {/if}
                        </td>
                      </tr>
                    {:else}
                      <tr>
                        <td colspan="2" class="text-center opacity-75"
                          >{t('contact.socials.empty')}</td
                        >
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="border-top pt-4 mt-4" id="form-contact">
            <h2 class="h3">{t('contact.form.title')}</h2>
            <p class="opacity-75">{@html t('contact.form.intro')}</p>

            {#key formKey}
              <Form
                {...localizeForm(store.form())}
                class="pt-1 d-flex flex-column gap-3"
                novalidate
                resetOnSuccess
                onSuccess={() => formKey++}
              >
                {#snippet children({ errors, processing })}
                  <div class="row g-3">
                    <Field.Group class="col-md-6">
                      <Field.Label for="fullName">{t('contact.form.name')}</Field.Label>
                      <Field.Input
                        id="fullName"
                        name="name"
                        placeholder={t('contact.form.name_placeholder')}
                        invalid={!!errors.name}
                      />
                      <Field.Feedback message={errors.name} />
                    </Field.Group>
                    <Field.Group class="col-md-6">
                      <Field.Label for="contactEmail"
                        >{@html t('contact.form.email')}</Field.Label>
                      <Field.Input
                        id="contactEmail"
                        name="email"
                        type="email"
                        placeholder={t('contact.form.email_placeholder')}
                        invalid={!!errors.email}
                      />
                      <Field.Feedback message={errors.email} />
                    </Field.Group>
                  </div>

                  <Field.Group>
                    <Field.Label for="contactReason">{t('contact.form.reason')}</Field.Label>
                    <Select
                      id="contactReason"
                      name="reason"
                      items={reasons}
                      required
                      searchable={false}
                      invalid={!!errors.reason}
                    />
                    <Field.Feedback message={errors.reason} />
                  </Field.Group>

                  <Field.Group>
                    <Field.Label>{t('contact.form.message')}</Field.Label>
                    <RichTextEditor
                      name="message"
                      minimal
                      placeholder={t('contact.form.message_placeholder')}
                      invalid={!!errors.message}
                    />
                    <Field.Feedback message={errors.message} />
                  </Field.Group>

                  <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
                    <button
                      type="submit"
                      class="btn btn-primary d-flex gap-2 align-items-center"
                      disabled={processing}
                    >
                      <span>{t('contact.form.send')}</span>
                      <Send size={16} />
                    </button>
                  </CaptchaSubmitRow>
                {/snippet}
              </Form>
            {/key}
          </div>
        </div>

        <div class="col-md-4">
          <div class="card sticky-top rounded-4 overflow-hidden">
            <div class="card-header p-4">
              <h2 class="h4 mb-0">{t('contact.nav.title')}</h2>
            </div>
            <div class="card-body">
              <ul class="nav nav-pills flex-column align-items-stretch">
                <li class="nav-item">
                  <a href="#information" class="nav-link py-3 w-100 text-start"
                    >{t('contact.nav.information')}</a>
                </li>
                <li class="nav-item">
                  <a href="#socmed" class="nav-link py-3 w-100 text-start"
                    >{t('contact.nav.socials')}</a>
                </li>
                <li class="nav-item">
                  <a href="#form-contact" class="nav-link py-3 w-100 text-start"
                    >{t('contact.nav.form')}</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
