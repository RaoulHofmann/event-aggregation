<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome to EventNest" />
    <div class="page">
        <div class="container">
            <!-- Header -->
            <header class="header">
                <h1 class="title">EventNest</h1>
                <p class="subtitle">Your Ultimate Event Aggregator</p>
            </header>

            <!-- Main Content -->
            <main class="main">
                <div class="content">
                    <!-- About Section -->
                    <section class="section">
                        <h2 class="section-title">About EventNest</h2>
                        <p class="section-text">
                            EventNest is a powerful event aggregator designed to simplify event discovery and management.
                            Built with an API-first approach, EventNest seamlessly integrates with your applications,
                            providing real-time access to a vast array of events. Stay tuned for our upcoming SDK,
                            making integration even easier!
                        </p>
                    </section>

                    <!-- Features Section -->
                    <section class="section">
                        <h2 class="section-title">Why Choose EventNest?</h2>
                        <div class="features-grid">
                            <div class="feature-card">
                                <h3 class="feature-title">API-First Design</h3>
                                <p class="feature-text">
                                    EventNest is built with developers in mind. Our robust API allows you to fetch,
                                    filter, and manage events programmatically.
                                </p>
                            </div>
                            <div class="feature-card">
                                <h3 class="feature-title">Upcoming SDK</h3>
                                <p class="feature-text">
                                    We're working on an SDK to make integration with EventNest even smoother.
                                    Stay tuned for updates!
                                </p>
                            </div>
                            <div class="feature-card">
                                <h3 class="feature-title">Comprehensive Event Data</h3>
                                <p class="feature-text">
                                    Access detailed event information, including dates, locations, descriptions,
                                    and more, all in one place.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Call to Action -->
                    <section class="cta-section">
                        <h2 class="cta-title">Get Started Today</h2>
                        <p class="cta-text">
                            Join the growing community of developers and event organizers using EventNest to
                            streamline their event management.
                        </p>
                        <div class="cta-buttons">
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="cta-button"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="cta-button"
                            >
                                Register
                            </Link>
                        </div>
                    </section>
                </div>
            </main>

            <!-- Footer -->
            <footer class="footer">
                <p>&copy; 2023 EventNest. All rights reserved.</p>
            </footer>
        </div>
    </div>
</template>

<style>
/* Define CSS Variables */
:root {
    --primary-color: #5c9ec9;
    --secondary-color: #f0f2f0;
    --text-color: #333;
    --text-light: #666;
    --white: #ffffff;
}

/* Global Styles */
.page {
    background-color: var(--secondary-color);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.container {
    max-width: 1200px;
    width: 100%;
    padding: 0 20px;
}

/* Header Styles */
.header {
    text-align: center;
    margin-bottom: 3rem;
}

.title {
    font-size: 3rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.subtitle {
    font-size: 1.5rem;
    color: var(--text-light);
}

/* Main Content Styles */
.main {
    background-color: var(--white);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 2rem;
}

.section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.section-text {
    font-size: 1.125rem;
    color: var(--text-light);
    line-height: 1.6;
}

/* Features Grid Styles */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.feature-card {
    background-color: var(--secondary-color);
    padding: 1.5rem;
    border-radius: 8px;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.75rem;
}

.feature-text {
    font-size: 1rem;
    color: var(--text-light);
    line-height: 1.5;
}

/* Call to Action Styles */
.cta-section {
    text-align: center;
    margin-top: 2rem;
}

.cta-title {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.cta-text {
    font-size: 1.125rem;
    color: var(--text-light);
    margin-bottom: 1.5rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.cta-button {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.cta-button:hover {
    background-color: #4a8bb5;
}

/* Footer Styles */
.footer {
    text-align: center;
    margin-top: 3rem;
    color: var(--text-light);
}
</style>
