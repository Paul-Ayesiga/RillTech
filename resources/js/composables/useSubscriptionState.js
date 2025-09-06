import {
    computed
} from 'vue';
import {
    usePage
} from '@inertiajs/vue3';

export function useSubscriptionState() {
    const page = usePage();

    const user = computed(() => page.props.auth && page.props.auth.user ? page.props.auth.user : null);

    const subscription = computed(() => user.value && user.value.subscription ? user.value.subscription : null);

    const subscriptionState = computed(() => {
        if (!user.value) return 'unauthenticated';

        if (!subscription.value) return 'no_subscription';

        const status = subscription.value.status;

        if (status === 'active') return 'active';
        if (status === 'cancelled' || subscription.value.cancel_at_period_end) return 'cancelled';
        if (status === 'past_due') return 'past_due';
        if (status === 'unpaid') return 'unpaid';
        if (subscription.value.trial_ends_at && new Date(subscription.value.trial_ends_at) > new Date()) return 'trial';

        return 'inactive';
    });

    const isAuthenticated = computed(() => !!user.value);

    const hasActiveSubscription = computed(() =>
        subscriptionState.value === 'active' || subscriptionState.value === 'trial'
    );

    const needsSubscription = computed(() =>
        isAuthenticated.value && !hasActiveSubscription.value
    );

    const canUpgrade = computed(() =>
        hasActiveSubscription.value && subscriptionState.value === 'active'
    );

    const subscriptionMessage = computed(() => {
        const context = page.props.subscription_context;
        const message = page.props.subscription_message;

        if (message) return message;

        // Handle specific contexts
        if (context === 'new_user') {
            return 'Welcome! Please choose a subscription plan to get started.';
        }
        if (context === 'returning_user') {
            return 'Welcome back! Please choose a subscription plan to continue.';
        }

        switch (subscriptionState.value) {
            case 'unauthenticated':
                return 'Sign up to get started with our powerful AI platform';
            case 'no_subscription':
                return 'Choose a plan to unlock all features';
            case 'cancelled':
                return 'Your subscription was cancelled. Reactivate to continue';
            case 'past_due':
                return 'Your payment is past due. Please update your payment method';
            case 'unpaid':
                return 'Your subscription is unpaid. Please resolve payment issues';
            case 'trial':
                return 'You\'re on a free trial. Upgrade to continue after trial ends';
            case 'active':
                return 'Upgrade your plan for more features';
            default:
                return 'Choose a plan that works for you';
        }
    });

    const ctaText = computed(() => {
        switch (subscriptionState.value) {
            case 'unauthenticated':
                return 'Get Started';
            case 'no_subscription':
                return 'Subscribe';
            case 'cancelled':
                return 'Reactivate';
            case 'past_due':
            case 'unpaid':
                return 'Update Payment';
            case 'trial':
                return 'Upgrade Now';
            case 'active':
                return 'Upgrade Plan';
            default:
                return 'Get Started';
        }
    });

    const shouldShowPricing = computed(() => {
        return page.props.show_pricing ||
            page.props.subscription_context ||
            window.location.hash === '#pricing';
    });

    return {
        user,
        subscription,
        subscriptionState,
        isAuthenticated,
        hasActiveSubscription,
        needsSubscription,
        canUpgrade,
        subscriptionMessage,
        ctaText,
        shouldShowPricing
    };
}
