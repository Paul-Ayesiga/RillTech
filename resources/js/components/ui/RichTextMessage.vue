<template>
  <div
    class="rich-text-content prose prose-sm max-w-none"
    v-html="formattedContent"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { marked } from 'marked';
import hljs from 'highlight.js/lib/core';

// Import common languages for code highlighting
import javascript from 'highlight.js/lib/languages/javascript';
import typescript from 'highlight.js/lib/languages/typescript';
import python from 'highlight.js/lib/languages/python';
import php from 'highlight.js/lib/languages/php';
import css from 'highlight.js/lib/languages/css';
import html from 'highlight.js/lib/languages/xml';
import json from 'highlight.js/lib/languages/json';
import bash from 'highlight.js/lib/languages/bash';

// Register languages
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('python', python);
hljs.registerLanguage('php', php);
hljs.registerLanguage('css', css);
hljs.registerLanguage('html', html);
hljs.registerLanguage('xml', html);
hljs.registerLanguage('json', json);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('shell', bash);

interface Props {
  content: string;
  isStreaming?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isStreaming: false
});

// Configure marked for ChatGPT-like rendering
marked.setOptions({
  highlight: function(code, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try {
        return hljs.highlight(code, { language: lang }).value;
      } catch (err) {
        console.warn('Highlight.js error:', err);
      }
    }
    return hljs.highlightAuto(code).value;
  },
  breaks: true, // Convert \n to <br>
  gfm: true, // GitHub Flavored Markdown
});

const formattedContent = computed(() => {
  if (!props.content) return '';

  let content = props.content;

  // Handle streaming cursor
  if (props.isStreaming && !content.endsWith(' ')) {
    content += '<span class="animate-pulse">|</span>';
  }

  // Pre-process content for better formatting
  content = preprocessContent(content);

  // Parse markdown
  const html = marked.parse(content);

  // Post-process for additional styling
  return postprocessHTML(html);
});

function preprocessContent(content: string): string {
  // Handle **bold** text that might not be properly spaced
  content = content.replace(/\*\*(.*?)\*\*/g, '**$1**');

  // Handle bullet points that might not be properly formatted
  content = content.replace(/^([•·▪▫‣⁃])\s*/gm, '- ');

  // Handle numbered lists
  content = content.replace(/^(\d+)\.\s*/gm, '$1. ');

  // Ensure proper line breaks for lists
  content = content.replace(/^(-|\d+\.)\s/gm, '\n$1 ');

  // Handle feature lists with emojis
  content = content.replace(/^([🎨🤖🔗⚡📊🔒💼🌟📱💡🚀🎯📈🛡️🔧📋])\s*\*\*(.*?)\*\*/gm, '\n- $1 **$2**');

  return content.trim();
}

function postprocessHTML(html: string): string {
  // Add simple classes that will be styled with CSS
  html = html.replace(/<h([1-6])>/g, '<h$1>');
  html = html.replace(/<p>/g, '<p>');
  html = html.replace(/<ul>/g, '<ul>');
  html = html.replace(/<ol>/g, '<ol>');
  html = html.replace(/<li>/g, '<li>');
  html = html.replace(/<blockquote>/g, '<blockquote>');
  html = html.replace(/<code>/g, '<code>');
  html = html.replace(/<pre><code/g, '<pre><code');
  html = html.replace(/<strong>/g, '<strong>');
  html = html.replace(/<em>/g, '<em>');

  // Handle tables with simple classes
  html = html.replace(/<table>/g, '<table>');
  html = html.replace(/<th>/g, '<th>');
  html = html.replace(/<td>/g, '<td>');

  return html;
}
</script>

<style scoped>
/* Custom styles for rich text content - using pure CSS to avoid Tailwind issues */
.rich-text-content {
  color: #1e293b;
  line-height: 1.625;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .rich-text-content {
    color: #e2e8f0;
  }
}

/* Code highlighting styles */
.rich-text-content pre {
  background-color: #0f172a;
  color: #f1f5f9;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 0.75rem 0;
}

.rich-text-content pre code {
  background: transparent;
  color: inherit;
  padding: 0;
}

/* Ensure proper spacing */
.rich-text-content > *:first-child {
  margin-top: 0;
}

.rich-text-content > *:last-child {
  margin-bottom: 0;
}

/* Link styles */
.rich-text-content a {
  color: #2563eb;
  text-decoration: underline;
}

.rich-text-content a:hover {
  color: #1d4ed8;
}

@media (prefers-color-scheme: dark) {
  .rich-text-content a {
    color: #60a5fa;
  }

  .rich-text-content a:hover {
    color: #93c5fd;
  }
}

/* Highlight.js theme adjustments */
.rich-text-content .hljs {
  background-color: #0f172a;
  color: #f1f5f9;
}

/* Syntax highlighting colors */
.rich-text-content .hljs-keyword {
  color: #a855f7;
}

.rich-text-content .hljs-string {
  color: #4ade80;
}

.rich-text-content .hljs-number {
  color: #facc15;
}

.rich-text-content .hljs-comment {
  color: #64748b;
}

/* Additional rich text elements */
.rich-text-content h1,
.rich-text-content h2,
.rich-text-content h3,
.rich-text-content h4,
.rich-text-content h5,
.rich-text-content h6 {
  font-weight: 700;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.rich-text-content p {
  margin-bottom: 0.75rem;
}

.rich-text-content ul,
.rich-text-content ol {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
}

.rich-text-content li {
  margin-bottom: 0.25rem;
}

.rich-text-content blockquote {
  border-left: 4px solid #3b82f6;
  padding-left: 1rem;
  font-style: italic;
  color: #64748b;
  margin: 0.75rem 0;
}

@media (prefers-color-scheme: dark) {
  .rich-text-content blockquote {
    color: #94a3b8;
  }
}

.rich-text-content code {
  background-color: #f1f5f9;
  color: #dc2626;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

@media (prefers-color-scheme: dark) {
  .rich-text-content code {
    background-color: #1e293b;
    color: #f87171;
  }
}

.rich-text-content strong {
  font-weight: 600;
}

.rich-text-content em {
  font-style: italic;
}

/* Table styles */
.rich-text-content table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #cbd5e1;
  margin: 0.75rem 0;
}

.rich-text-content th,
.rich-text-content td {
  border: 1px solid #cbd5e1;
  padding: 0.75rem;
  text-align: left;
}

.rich-text-content th {
  background-color: #f1f5f9;
  font-weight: 600;
}

@media (prefers-color-scheme: dark) {
  .rich-text-content table {
    border-color: #475569;
  }

  .rich-text-content th,
  .rich-text-content td {
    border-color: #475569;
  }

  .rich-text-content th {
    background-color: #1e293b;
  }
}
</style>
