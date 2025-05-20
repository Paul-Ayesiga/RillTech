import { Ref } from 'vue'

/**
 * Helper function for TanStack Table state management in Vue
 */
export function valueUpdater<T>(updaterOrValue: ((old: T) => T) | T, ref: Ref<T>) {
  if (typeof updaterOrValue === 'function') {
    ref.value = (updaterOrValue as (old: T) => T)(ref.value)
  } else {
    ref.value = updaterOrValue
  }
}
