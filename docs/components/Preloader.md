# Preloader Component

A modern, animated preloader component with progress tracking and smooth transitions for the RillTech landing page.

## Features

- ✨ **Animated Logo**: Rotating ring with pulsing icon
- 📊 **Progress Bar**: Real-time progress with glow effects
- 🔢 **Percentage Display**: Shows loading percentage
- 🎯 **Bouncing Dots**: Animated loading indicators
- ✅ **Success State**: Checkmark when complete
- 🎭 **Smooth Transitions**: Fade in/out animations
- 🎨 **Theme Integration**: Matches app design system
- 📱 **Responsive**: Works on all screen sizes

## Usage

### Basic Usage

```vue
<template>
  <Preloader :show="isLoading" />
</template>
```

### Advanced Usage

```vue
<template>
  <Preloader 
    :show="isLoading"
    logo-text="Your Brand"
    loading-text="Please wait..."
    :duration="3000"
    @loaded="onLoadComplete"
  />
</template>

<script setup>
const isLoading = ref(true);

const onLoadComplete = () => {
  console.log('Loading complete!');
};
</script>
```

### Using the Composable

```vue
<script setup>
import { preloader } from '@/composables/usePreloader';

// Show preloader
preloader.show();

// Set progress manually
preloader.setProgress(50);

// Simulate loading
await preloader.simulateLoading(2000);

// Hide preloader
preloader.hide();
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `show` | `boolean` | `true` | Controls visibility |
| `duration` | `number` | `2000` | Auto-hide duration (0 = manual control) |
| `logoText` | `string` | `'RillTech'` | Brand text to display |
| `loadingText` | `string` | `'Loading...'` | Loading message |

## Events

| Event | Description |
|-------|-------------|
| `@loaded` | Emitted when loading completes |

## Composable API

### Methods

- `show()` - Show the preloader
- `hide()` - Hide the preloader
- `setProgress(progress: number)` - Set progress (0-100)
- `simulateLoading(duration: number)` - Simulate loading
- `initializeApp()` - Initialize app with preloader

### Reactive Properties

- `isLoading` - Current loading state
- `loadingProgress` - Current progress (0-100)

## Implementation

The preloader is integrated into the landing page (`resources/js/pages/frontend/Landing.vue`) and shows during:

1. **Page Load** (10%) - Initial setup
2. **DOM Ready** (20%) - HTML structure loaded
3. **Assets Loaded** (50%) - Images, CSS, JS loaded
4. **GSAP Init** (70%) - Animations initialized
5. **Scroll Setup** (85%) - Scroll tracking ready
6. **Navbar Animation** (95%) - Navigation animated
7. **Complete** (100%) - Everything ready

## Styling

The component uses:
- CSS custom properties for theming
- Tailwind CSS classes for layout
- Custom animations for smooth effects
- Backdrop blur for modern glass effect

## Performance

- Lazy loading support
- Minimal DOM impact
- Efficient animations
- Memory cleanup on unmount
