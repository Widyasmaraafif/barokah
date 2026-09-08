<script setup>
import { onBeforeUnmount } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = new Editor({
    content: props.modelValue,
    extensions: [
        StarterKit,
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

onBeforeUnmount(() => {
    editor.destroy()
})
</script>

<template>
    <div class="overflow-hidden rounded-md border">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-1 border-b bg-muted/30 p-2">
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-muted': editor.isActive('bold') }"
                class="rounded px-3 py-1 text-sm font-bold hover:bg-muted"
            >
                B
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-muted': editor.isActive('italic') }"
                class="rounded px-3 py-1 text-sm italic hover:bg-muted"
            >
                I
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="{ 'bg-muted': editor.isActive('underline') }"
                class="rounded px-3 py-1 text-sm underline hover:bg-muted"
            >
                U
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                • List
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                1. List
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                H2
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                H3
            </button>

            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                ↶
            </button>

            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                class="rounded px-3 py-1 text-sm hover:bg-muted"
            >
                ↷
            </button>
        </div>

        <!-- Editor -->
        <EditorContent
            :editor="editor"
            class="min-h-[200px] w-full px-3 py-2 text-sm"
        />
    </div>
</template>

<style>
.ProseMirror {
    min-height: 180px;
    outline: none;
}

.ProseMirror p {
    margin: 0.5rem 0;
}

.ProseMirror h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 1rem 0 0.5rem;
}

.ProseMirror h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem;
}

.ProseMirror ul {
    list-style: disc;
    padding-left: 1.5rem;
}

.ProseMirror ol {
    list-style: decimal;
    padding-left: 1.5rem;
}

.ProseMirror strong {
    font-weight: 700;
}

.ProseMirror em {
    font-style: italic;
}
</style>