<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApiTokenController from '@/actions/App/Http/Controllers/Settings/ApiTokenController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/api-tokens';

/**
 * API token metadata, as serialized by App\Http\Resources\ApiTokenResource.
 * The secret is never sent here; only `newToken` (flashed once on create) carries it.
 */
type ApiToken = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string | null;
};

// oxfmt-ignore
type Props = {
    tokens: ApiToken[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'API tokens',
                href: index(),
            },
        ],
    },
});

const page = usePage();
const newToken = computed(() => {
    const flashed = (page.flash as Record<string, unknown>).newToken;

    return typeof flashed === 'string' ? flashed : null;
});

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : 'Never';
}
</script>

<template>
    <Head title="API tokens" />

    <h1 class="sr-only">API tokens</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Create API token"
            description="Tokens let scripts call the PixelForge API (/api/v1) on your behalf"
        />

        <Form
            v-bind="ApiTokenController.store.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Token name</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    required
                    maxlength="255"
                    placeholder="e.g. CI script"
                />
                <InputError :message="errors.name" />
            </div>

            <Button :disabled="processing" data-test="create-api-token-button"
                >Create token</Button
            >
        </Form>

        <div
            v-if="newToken"
            class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-200/10 dark:bg-amber-700/10"
            data-test="new-api-token"
        >
            <p class="text-sm font-medium">
                Copy your new token now. It will not be shown again.
            </p>
            <code class="block rounded bg-background p-2 text-sm break-all">{{
                newToken
            }}</code>
        </div>
    </div>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Your tokens"
            description="Revoke a token to stop it from working immediately"
        />

        <p v-if="tokens.length === 0" class="text-sm text-muted-foreground">
            You have no API tokens yet.
        </p>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="token in tokens"
                :key="token.id"
                class="flex items-center justify-between gap-4 p-4"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ token.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        Created {{ formatDate(token.created_at) }} · Last used
                        {{ formatDate(token.last_used_at) }}
                    </p>
                </div>

                <Form
                    v-bind="ApiTokenController.destroy.form(token.id)"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="processing"
                        data-test="revoke-api-token-button"
                        >Revoke</Button
                    >
                </Form>
            </li>
        </ul>
    </div>
</template>
