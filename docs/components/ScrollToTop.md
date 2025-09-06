# ScrollToTop Component

A smooth, animated scroll-to-top button with progress indicator and customizable appearance for enhanced user experience.

## Features

- 🚀 **Smooth Scrolling**: GSAP-powered smooth scrolling animation
- 📊 **Progress Ring**: Visual indicator showing scroll progress
- 🎨 **Customizable**: Multiple sizes, positions, and variants
- 📱 **Responsive**: Works perfectly on all screen sizes
- ♿ **Accessible**: Proper ARIA labels and keyboard support
- 🎭 **Animated**: Smooth entrance/exit transitions
- 🔄 **Loading State**: Shows spinner during scroll animation
- 🎯 **Smart Visibility**: Appears after configurable scroll distance

## Usage

### Basic Usage

```vue
<template>
  <ScrollToTop />
</template>
```

### Advanced Configuration

```vue
<template>
  <ScrollToTop 
    :show-after="400"
    position="bottom-right"
    size="md"
    variant="default"
    :smooth="true"
    :duration="1200"
    @scroll-start="onScrollStart"
    @scroll-complete="onScrollComplete"
  />
</template>

<script setup>
const onScrollStart = () => {
  console.log('Scrolling started');
};

const onScrollComplete = () => {
  console.log('Scrolling completed');
};
</script>
```

### Using the Composable

```vue
<script setup>
import { useScrollToTop } from '@/composables/useScrollToTop';

const { scrollToTop, scrollToElement, getScrollPosition } = useScrollToTop();

// Scroll to top programmatically
await scrollToTop({ smooth: true, duration: 1500 });

// Scroll to specific element
await scrollToElement('#target-section', { offset: -100 });

// Get current scroll position
const position = getScrollPosition();
console.log(`X: ${position.x}, Y: ${position.y}`);
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `showAfter` | `number` | `300` | Pixels to scroll before showing button |
| `position` | `'bottom-right' \| 'bottom-left' \| 'bottom-center'` | `'bottom-right'` | Button position |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` | Button size |
| `variant` | `'default' \| 'outline' \| 'ghost'` | `'default'` | Button style variant |
| `smooth` | `boolean` | `true` | Enable smooth scrolling |
| `duration` | `number` | `1000` | Scroll animation duration (ms) |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `@scroll-start` | `void` | Emitted when scroll animation starts |
| `@scroll-complete` | `void` | Emitted when scroll animation completes |

## Composable API

### Methods

- `scrollToTop(options?)` - Scroll to top of page
- `scrollToElement(selector, options?)` - Scroll to specific element
- `getScrollPosition()` - Get current scroll position
- `isElementInViewport(element, threshold?)` - Check if element is visible
- `setupScrollTracking(showAfter?)` - Setup scroll event listeners

### Options

```typescript
interface ScrollOptions {
  smooth?: boolean;    // Enable smooth scrolling
  duration?: number;   // Animation duration in ms
  offset?: number;     // Offset from target position
}
```

### Reactive Properties

- `isVisible` - Whether button should be visible
- `isScrolling` - Whether scroll animation is active
- `scrollProgress` - Current scroll progress (0-100)

## Styling

### Size Variants

- **Small (`sm`)**: 40x40px button
- **Medium (`md`)**: 48x48px button  
- **Large (`lg`)**: 56x56px button

### Style Variants

- **Default**: Primary background with white text
- **Outline**: Border with transparent background
- **Ghost**: Semi-transparent background

### Position Options

- **Bottom Right**: Fixed to bottom-right corner
- **Bottom Left**: Fixed to bottom-left corner
- **Bottom Center**: Centered at bottom

## Customization

### Custom Styling

```vue
<style>
/* Override button styles */
.scroll-to-top-custom {
  background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
  border-radius: 50%;
}

/* Custom progress ring */
.scroll-to-top-custom svg path {
  stroke: #ffffff;
  stroke-width: 3;
}
</style>
```

### Custom Animation

```vue
<script setup>
import { gsap } from 'gsap';

const customScrollToTop = () => {
  gsap.to(window, {
    duration: 2,
    scrollTo: { y: 0 },
    ease: 'elastic.out(1, 0.3)'
  });
};
</script>
```

## Integration

The ScrollToTop component is integrated into the landing page and appears when users scroll down more than 400px. It provides:

1. **Visual Feedback**: Progress ring shows scroll position
2. **Smooth Animation**: GSAP-powered smooth scrolling
3. **User Experience**: Quick way to return to top
4. **Performance**: Throttled scroll events for efficiency

## Accessibility

- Proper ARIA labels and roles
- Keyboard navigation support
- Focus management
- Screen reader friendly
- High contrast support

## Performance

- Throttled scroll event handling
- Efficient DOM updates
- Minimal memory footprint
- Smooth 60fps animations
- Lazy loading support
