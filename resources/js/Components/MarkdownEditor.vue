<script setup>
import { onMounted, onBeforeUnmount, watch, ref } from 'vue'
import { EditorState } from '@codemirror/state'
import { EditorView, keymap, lineNumbers, drawSelection } from '@codemirror/view'
import { defaultKeymap, indentWithTab, history, historyKeymap } from '@codemirror/commands'
import { markdown } from '@codemirror/lang-markdown'
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
    '&': { minHeight: '400px', fontSize: '13px' },
    '.cm-scroller': {
      fontFamily: "'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace",
      lineHeight: '1.7',
      overflow: 'auto',
    },
    '.cm-content': { padding: '12px 0' },
    '.cm-line': { padding: '0 16px' },
    '.cm-gutters': { borderRadius: '0.375rem 0 0 0.375rem', paddingRight: '4px' },
    '.cm-activeLineGutter': { backgroundColor: 'transparent' },
  })

  view = new EditorView({
    state: EditorState.create({
      doc: props.modelValue ?? '',
      extensions: [
        history(),
        lineNumbers(),
        drawSelection(),
        keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
        markdown(),
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
