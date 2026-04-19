<template>
  <div class="tiptap-editor" :class="{ 'is-focused': editor?.isFocused }">
    <!-- Toolbar -->
    <div class="toolbar">
      <template v-if="!htmlMode">
        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()" title="Bold">
            <Bold class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()" title="Italic">
            <Italic class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()" title="Underline">
            <UnderlineIcon class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('strike') }" @click="editor.chain().focus().toggleStrike().run()" title="Strikethrough">
            <Strikethrough class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="toolbar-divider"/>

        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" title="Heading 2">
            <Heading2 class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading 3">
            <Heading3 class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="toolbar-divider"/>

        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()" title="Bullet list">
            <List class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()" title="Ordered list">
            <ListOrdered class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('blockquote') }" @click="editor.chain().focus().toggleBlockquote().run()" title="Blockquote">
            <Quote class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('code') }" @click="editor.chain().focus().toggleCode().run()" title="Code">
            <Code class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="toolbar-divider"/>

        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }" @click="editor.chain().focus().setTextAlign('left').run()" title="Align left">
            <AlignLeft class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }" @click="editor.chain().focus().setTextAlign('center').run()" title="Align center">
            <AlignCenter class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }" @click="editor.chain().focus().setTextAlign('right').run()" title="Align right">
            <AlignRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="toolbar-divider"/>

        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" :disabled="!editor?.can().undo()" @click="editor.chain().focus().undo().run()" title="Undo">
            <Undo2 class="w-3.5 h-3.5" />
          </button>
          <button type="button" class="toolbar-btn" :disabled="!editor?.can().redo()" @click="editor.chain().focus().redo().run()" title="Redo">
            <Redo2 class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="toolbar-divider"/>
        <div class="toolbar-group">
          <button type="button" class="toolbar-btn" @click="pickerOpen = true" title="Insert image">
            <ImageIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </template>

      <span v-else class="text-xs text-muted-foreground/70 select-none pl-1">HTML source</span>

      <div class="ml-auto flex items-center gap-2 pr-1">
        <span v-if="!htmlMode" class="text-xs text-muted-foreground/70 select-none">{{ wordCount }} words</span>
        <div class="toolbar-divider" />
        <!-- HTML source toggle -->
        <button
          type="button"
          class="toolbar-btn"
          :class="{ 'is-active': htmlMode }"
          title="Toggle HTML source"
          @click="toggleHtmlMode"
        >
          <Code2 class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <MediaPicker
      v-model="pickerOpen"
      confirm-label="Insert image"
      :dark="dark"
      @select="(m) => insertImage(m.url, m.alt)"
    />

    <!-- Visual editor -->
    <EditorContent v-if="!htmlMode" :editor="editor" class="editor-body" />

    <!-- HTML source editor -->
    <textarea
      v-else
      v-model="rawHtml"
      class="editor-body html-source"
      spellcheck="false"
      @input="onHtmlInput"
    />
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
import {
  Bold, Italic, Underline as UnderlineIcon, Strikethrough,
  Heading2, Heading3, List, ListOrdered, Quote, Code,
  AlignLeft, AlignCenter, AlignRight, Undo2, Redo2, ImageIcon, Code2,
} from "lucide-vue-next";

const props = defineProps({
  modelValue:  { type: String,  default: "" },
  placeholder: { type: String,  default: "Start writing your post..." },
  dark:        { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);

const pickerOpen = ref(false)
const htmlMode   = ref(false)
const rawHtml    = ref('')

function toggleHtmlMode() {
  if (!htmlMode.value) {
    // Entering HTML mode — snapshot current content into the textarea
    rawHtml.value = editor.value?.getHTML() ?? ''
  } else {
    // Leaving HTML mode — push textarea content back into the editor
    editor.value?.commands.setContent(rawHtml.value, false)
    emit('update:modelValue', rawHtml.value)
  }
  htmlMode.value = !htmlMode.value
}

function onHtmlInput() {
  // Live-sync as the user types in the textarea so the model stays up-to-date
  emit('update:modelValue', rawHtml.value)
}

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
:deep(.toolbar-btn:hover:not(:disabled)) { background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary); }
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

.html-source {
  flex: 1;
  width: 100%;
  min-height: 22rem;
  padding: 1.25rem 1.5rem;
  font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', monospace;
  font-size: 0.8125rem;
  line-height: 1.7;
  color: var(--foreground);
  background: var(--background);
  border: none;
  outline: none;
  resize: vertical;
  tab-size: 2;
}
</style>
