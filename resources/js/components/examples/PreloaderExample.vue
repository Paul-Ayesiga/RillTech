<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { preloader } from '@/composables/usePreloader';

const triggerPreloader = () => {
  preloader.simulateLoading(3000);
};

const showPreloader = () => {
  preloader.show();
};

const hidePreloader = () => {
  preloader.hide();
};
</script>

<template>
  <div class="px-3 space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Preloader Component</CardTitle>
        <CardDescription>
          A modern, animated preloader component with progress tracking and smooth transitions.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Button @click="triggerPreloader" variant="default">
            Simulate Loading (3s)
          </Button>
          <Button @click="showPreloader" variant="outline">
            Show Preloader
          </Button>
          <Button @click="hidePreloader" variant="secondary">
            Hide Preloader
          </Button>
        </div>
        
        <div class="text-sm text-muted-foreground">
          <p><strong>Features:</strong></p>
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Animated logo with rotating ring</li>
            <li>Progress bar with glow effects</li>
            <li>Loading percentage display</li>
            <li>Bouncing dots animation</li>
            <li>Success state with checkmark</li>
            <li>Smooth fade in/out transitions</li>
            <li>Customizable text and duration</li>
            <li>Global state management</li>
          </ul>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
