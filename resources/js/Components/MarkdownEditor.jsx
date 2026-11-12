import { useEffect, useRef } from 'react';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

/** Editor Markdown (TOAST UI Editor) untuk form artikel. */
export default function MarkdownEditor({ value, onChange, height = '55vh' }) {
    const host = useRef(null);
    const latestOnChange = useRef(onChange);

    useEffect(() => {
        latestOnChange.current = onChange;
    });

    useEffect(() => {
        const editor = new Editor({
            el: host.current,
            height,
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: value ?? '',
            placeholder: 'Tulis artikel dengan format Markdown…',
            usageStatistics: false,
            events: {
                change: () => latestOnChange.current(editor.getMarkdown()),
            },
        });

        return () => editor.destroy();
        // Editor dibuat sekali; nilai awal hanya dibaca saat mount.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <div ref={host} />;
}
