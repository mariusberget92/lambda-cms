<template>
  <div class="tiptap-editor" :class="{ 'is-focused': editor?.isFocused }">
    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()" title="Bold">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M15.6 11.8c.9-.6 1.5-1.6 1.5-2.8C17.1 6.6 15.5 5 13.1 5H7v14h6.6c2.4 0 4.4-1.8 4.4-4.2 0-1.6-.9-2.9-2.4-3zm-6.1-4.6h3c.8 0 1.5.7 1.5 1.5s-.7 1.5-1.5 1.5h-3V7.2zm3.6 9.6h-3.6v-3.4h3.6c1 0 1.7.8 1.7 1.7s-.8 1.7-1.7 1.7z"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()" title="Italic">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()" title="Underline">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M7 4v6a5 5 0 0010 0V4M5 20h14"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('strike') }" @click="editor.chain().focus().toggleStrike().run()" title="Strikethrough">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17.3 12H6.7M12 5a4 4 0 00-4 3.5M12 19a4 4 0 004-3.5"/></svg>
        </button>
      </div>

      <div class="toolbar-divider"/>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" title="Heading 2">
          <span class="text-xs font-bold">H2</span>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading 3">
          <span class="text-xs font-bold">H3</span>
        </button>
      </div>

      <div class="toolbar-divider"/>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()" title="Bullet list">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()" title="Ordered list">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('blockquote') }" @click="editor.chain().focus().toggleBlockquote().run()" title="Blockquote">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('code') }" @click="editor.chain().focus().toggleCode().run()" title="Code">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
        </button>
      </div>

      <div class="toolbar-divider"/>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }" @click="editor.chain().focus().setTextAlign('left').run()" title="Align left">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 6h18M3 12h12M3 18h15"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }" @click="editor.chain().focus().setTextAlign('center').run()" title="Align center">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 6h18M6 12h12M4 18h16"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }" @click="editor.chain().focus().setTextAlign('right').run()" title="Align right">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 6h18M9 12h12M6 18h15"/></svg>
        </button>
      </div>

      <div class="toolbar-divider"/>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" :disabled="!editor?.can().undo()" @click="editor.chain().focus().undo().run()" title="Undo">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
        </button>
        <button type="button" class="toolbar-btn" :disabled="!editor?.can().redo()" @click="editor.chain().focus().redo().run()" title="Redo">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
        </button>
      </div>

      <div class="toolbar-divider"/>
      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" @click="pickerOpen = true" title="Insert image">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </button>
      </div>

      <div class="ml-auto flex items-center text-xs text-muted-foreground/70 select-none pr-1">
        {{ wordCount }} words
      </div>
    </div>

    <MediaPicker
      v-model="pickerOpen"
      confirm-label="Insert image"
      @select="(m) => insertImage(m.url, m.alt)"
    />

    <!-- Content -->
    <EditorContent :editor="editor" class="editor-body" />
  </div>
</template>

<script setup>
import { ref, computed, onBeforeUnmount, watch } from "vue";
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Placeholder from "@tiptap/extension-placeholder";
import CharacterCount from "@tiptap/extension-character-count";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import Image from "@tiptap/extension-image";
import MediaPicker from "@/Components/MediaPicker.vue";

const props = defineProps({
  modelValue: { type: String, default: "" },
  placeholder: { type: String, default: "Start writing your post..." },
});
const emit = defineEmits(["update:modelValue"]);

const pickerOpen = ref(false)

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    Placeholder.configure({ placeholder: props.placeholder }),
    CharacterCount,
    TextAlign.configure({ types: ["heading", "paragraph"] }),
    Image.configure({ inline: false }),
  ],
  editorProps: { attributes: { class: "prose-editor" } },
  onUpdate({ editor }) {
    emit("update:modelValue", editor.getHTML());
  },
});

watch(() => props.modelValue, (value) => {
  if (editor.value && editor.value.getHTML() !== value) {
    editor.value.commands.setContent(value ?? "", false);
  }
});

const wordCount = computed(() => editor.value?.storage.characterCount.words() ?? 0);
onBeforeUnmount(() => editor.value?.destroy());

function insertImage(url, alt) {
  editor.value?.chain().focus().setImage({ src: url, alt: alt ?? '' }).run()
}
defineExpose({ insertImage })
</script>

<style scoped>
.tiptap-editor {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--background);
  transition: box-shadow 0.15s;
}
.tiptap-editor.is-focused { box-shadow: 0 0 0 2px var(--ring); }

.toolbar {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 0.375rem 0.5rem;
  border-bottom: 1px solid var(--border);
  background: var(--muted);
  flex-wrap: wrap;
}
.toolbar-group { display: flex; align-items: center; gap: 1px; }
.toolbar-divider { width: 1px; height: 1.25rem; background: var(--border); margin: 0 0.25rem; }

:deep(.toolbar-btn) {
  display: inline-flex; align-items: center; justify-content: center;
  width: 1.875rem; height: 1.875rem;
  border-radius: var(--radius-md); border: none;
  background: transparent; color: var(--muted-foreground);
  cursor: pointer; transition: background 0.1s, color 0.1s;
}
:deep(.toolbar-btn:hover:not(:disabled)) { background: var(--accent); color: var(--accent-foreground); }
:deep(.toolbar-btn.is-active) { background: var(--primary); color: var(--primary-foreground); }
:deep(.toolbar-btn:disabled) { opacity: 0.35; cursor: not-allowed; }

.editor-body { flex: 1; }

:deep(.tiptap) {
  padding: 1.25rem 1.5rem;
  min-height: 22rem;
  outline: none;
  font-size: 0.9375rem;
  line-height: 1.75;
  color: var(--foreground);
}
:deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: var(--muted-foreground);
  pointer-events: none; float: left; height: 0;
}
:deep(.prose-editor h2) { font-size: 1.375rem; font-weight: 700; margin: 1.5rem 0 0.5rem; line-height: 1.3; }
:deep(.prose-editor h3) { font-size: 1.125rem; font-weight: 600; margin: 1.25rem 0 0.375rem; line-height: 1.4; }
:deep(.prose-editor p) { margin: 0.5rem 0; }
:deep(.prose-editor strong) { font-weight: 600; }
:deep(.prose-editor em) { font-style: italic; }
:deep(.prose-editor u) { text-decoration: underline; }
:deep(.prose-editor s) { text-decoration: line-through; }
:deep(.prose-editor ul) { list-style: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
:deep(.prose-editor ol) { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
:deep(.prose-editor li) { margin: 0.125rem 0; }
:deep(.prose-editor blockquote) { border-left: 3px solid var(--border); padding-left: 1rem; color: var(--muted-foreground); margin: 0.75rem 0; font-style: italic; }
:deep(.prose-editor code) { background: var(--muted); padding: 0.125rem 0.375rem; border-radius: var(--radius-sm); font-size: 0.85em; font-family: ui-monospace, monospace; }
:deep(.prose-editor pre) { background: var(--muted); padding: 1rem; border-radius: var(--radius-md); overflow-x: auto; margin: 0.75rem 0; }
:deep(.prose-editor pre code) { background: none; padding: 0; }
:deep(.prose-editor hr) { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }
:deep(.prose-editor .text-left)   { text-align: left; }
:deep(.prose-editor .text-center) { text-align: center; }
:deep(.prose-editor .text-right)  { text-align: right; }
:deep(.prose-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  margin: 0.75rem 0;
}
</style>
