<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Primitive } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props {
  as?: string
  class?: HTMLAttributes['class']
}

withDefaults(defineProps<Props>(), {
  as: 'span'
})
</script>

<template>
  <Primitive
    :as="as"
    :class="cn(
      'absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0',
      'clip-[rect(0,0,0,0)]',
      $props.class
    )"
  >
    <slot />
  </Primitive>
</template>
