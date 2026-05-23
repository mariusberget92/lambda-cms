<!-- resources/js/Components/JsEditor.vue -->
<!-- Thin CodeMirror 6 wrapper for JavaScript editing with one-dark theme -->
<script setup>
import { onMounted, onBeforeUnmount, watch, ref } from 'vue'
import { EditorState } from '@codemirror/state'
import { EditorView, keymap, lineNumbers } from '@codemirror/view'
import { defaultKeymap, indentWithTab } from '@codemirror/commands'
import { javascript } from '@codemirror/lang-javascript'
import { oneDark } from '@codemirror/theme-one-dark'

const props = defineProps({
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const container = ref(null)
let view = null

onMounted(() => {
  const updateListener = EditorView.updateListener.of((update) => {
    if (update.docChanged) {
      emit('update:modelValue', update.state.doc.toString())
    }
  })

  const baseTheme = EditorView.theme({
    '&': { borderRadius: '0.375rem', fontSize: '11px', minHeight: '200px' },
    '.cm-scroller': { fontFamily: "'JetBrains Mono', 'Fira Code', monospace", overflow: 'auto' },
    '.cm-content': { padding: '6px 0' },
    '.cm-line': { padding: '0 6px' },
    '.cm-gutters': { borderRadius: '0.375rem 0 0 0.375rem' },
  })

  view = new EditorView({
    state: EditorState.create({
      doc: props.modelValue ?? '',
      extensions: [
        lineNumbers(),
        keymap.of([...defaultKeymap, indentWithTab]),
        javascript(),
        oneDark,
        baseTheme,
        updateListener,
        EditorView.lineWrapping,
      ],
    }),
    parent: container.value,
  })
})

onBeforeUnmount(() => {
  view?.destroy()
})

watch(() => props.modelValue, (val) => {
  if (!view) return
  const current = view.state.doc.toString()
  if (current !== val) {
    view.dispatch({
      changes: { from: 0, to: current.length, insert: val ?? '' },
    })
  }
})
</script>

<template>
  <div ref="container" class="overflow-hidden rounded-md border border-border" />
</template>
