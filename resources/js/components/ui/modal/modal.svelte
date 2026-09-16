<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { Modal as BsModal } from 'bootstrap';
    import { cn } from '@/lib/utils';

    type Size = 'sm' | 'default' | 'lg' | 'xl';

    const sizeClass: Record<Size, string | undefined> = {
        sm: 'modal-sm',
        default: undefined,
        lg: 'modal-lg',
        xl: 'modal-xl',
    };

    let {
        children,
        open = $bindable(false),
        id,
        size = 'default',
        centered = true,
        scrollable = false,
        /** `false` also disables Escape-to-close, matching Bootstrap's `data-bs-keyboard`. */
        closeOnBackdrop = true,
        onshow,
        onshown,
        onhide,
        onhidden,
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        open?: boolean;
        id?: string;
        size?: Size;
        centered?: boolean;
        scrollable?: boolean;
        closeOnBackdrop?: boolean;
        onshow?: () => void;
        onshown?: () => void;
        onhide?: () => void;
        onhidden?: () => void;
        class?: string;
        [key: string]: unknown;
    } = $props();

    let el: HTMLElement | undefined = $state();
    let instance: BsModal | undefined;

    // Ported from Batamtix Core's modal driving pattern (`confirmation-modal.ts`,
    // `otp-modal.ts`): a `bootstrap.Modal` instance owns the actual show/hide
    // animation and focus trap, kept in sync with the `open` bindable so the
    // rest of the app can drive the modal declaratively instead of calling
    // Bootstrap's imperative API directly.
    $effect(() => {
        if (!el) {
            return;
        }

        let disposed = false;
        const node = el;

        const handleShow = () => onshow?.();
        const handleShown = () => onshown?.();
        const handleHide = () => onhide?.();
        const handleHidden = () => {
            open = false;
            onhidden?.();
        };

        node.addEventListener('show.bs.modal', handleShow);
        node.addEventListener('shown.bs.modal', handleShown);
        node.addEventListener('hide.bs.modal', handleHide);
        node.addEventListener('hidden.bs.modal', handleHidden);

        void import('bootstrap').then(({ Modal }) => {
            if (disposed) {
                return;
            }

            instance = Modal.getOrCreateInstance(node, {
                backdrop: closeOnBackdrop ? true : 'static',
                keyboard: closeOnBackdrop,
            });

            if (open) {
                instance.show();
            }
        });

        return () => {
            disposed = true;
            node.removeEventListener('show.bs.modal', handleShow);
            node.removeEventListener('shown.bs.modal', handleShown);
            node.removeEventListener('hide.bs.modal', handleHide);
            node.removeEventListener('hidden.bs.modal', handleHidden);
            instance?.dispose();
            instance = undefined;
        };
    });

    $effect(() => {
        if (!instance) {
            return;
        }

        if (open) {
            instance.show();
        } else {
            instance.hide();
        }
    });
</script>

<div
    bind:this={el}
    class="modal fade"
    {id}
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    {...rest}
>
    <div
        class={cn(
            'modal-dialog',
            centered && 'modal-dialog-centered',
            scrollable && 'modal-dialog-scrollable',
            sizeClass[size],
            className,
        )}
    >
        <div class="modal-content">
            {@render children?.()}
        </div>
    </div>
</div>
