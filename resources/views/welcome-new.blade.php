<x-welcome-layout 
    title="Activity Tracking System - Professional Support Team Management"
    description="Streamline your support team's daily activities with professional tracking, collaboration, and reporting tools designed for modern support workflows.">

    <!-- Hero Section -->
    <x-hero-section 
        headline="Activity Tracking System"
        subtitle="Streamline your support team's daily activities with professional tracking, collaboration, and reporting tools designed for modern support workflows."
        ctaText="Sign In to Get Started"
        ctaUrl="{{ route('login') }}"
        :showCta="true" />

    <!-- Features Grid Section -->
    <x-features-grid 
        sectionTitle="Key Features"
        sectionSubtitle="Everything you need to manage your support team's activities effectively" />

</x-welcome-layout>