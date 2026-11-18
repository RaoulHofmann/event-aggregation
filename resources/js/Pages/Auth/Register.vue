<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ turnstileSiteKey: String });
const captcha = ref(null);
const captchaError = ref('');
let widgetId;
let script;

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
    company_website: '',
    turnstile_token: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation', 'turnstile_token');
            if (widgetId !== undefined) window.turnstile?.reset(widgetId);
        },
    });
};
onMounted(() => {
    if (!props.turnstileSiteKey) return;

    const render = () => {
        if (!captcha.value) return;
        widgetId = window.turnstile.render(captcha.value, {
            sitekey: props.turnstileSiteKey,
            action: 'register',
            callback: (token) => {
                form.turnstile_token = token;
                captchaError.value = '';
            },
            'expired-callback': () => form.reset('turnstile_token'),
            'error-callback': () => {
                form.reset('turnstile_token');
                captchaError.value = 'The spam check could not load. Reload the page to try again.';
            },
        });
    };

    if (window.turnstile) {
        render();
        return;
    }

    script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.onload = render;
    script.onerror = () => captchaError.value = 'The spam check could not load. Reload the page to try again.';
    document.head.appendChild(script);
});

onBeforeUnmount(() => {
    if (widgetId !== undefined) window.turnstile?.remove(widgetId);
    if (script) {
        script.onload = null;
        script.onerror = null;
        script.remove();
    }
});
</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <h1 class="mb-2 text-2xl font-semibold text-slate-950">Your events start here</h1>
        <p class="mb-7 text-sm text-slate-500">Request an account. An administrator will review your registration before you can open a workspace.</p>
        <form @submit.prevent="submit">
            <div hidden aria-hidden="true">
                <label for="company-website">Leave this field blank</label>
                <input id="company-website" v-model="form.company_website" name="company_website" type="text" tabindex="-1" autocomplete="off" />
            </div>
            <InputError class="mb-4" :message="form.errors.registration_check" />
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                        <div class="ms-2">
                            I agree to the
                            <a
                                target="_blank"
                                :href="route('terms.show')"
                                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                >Terms of Service</a
                            >
                            and
                            <a
                                target="_blank"
                                :href="route('policy.show')"
                                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                >Privacy Policy</a
                            >
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div v-if="turnstileSiteKey" class="mt-4">
                <div ref="captcha" />
                <InputError class="mt-2" :message="captchaError" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || (turnstileSiteKey && !form.turnstile_token)"
                >
                    Request access
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
