import { useEffect, useRef } from 'react';
import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';

/** Merender konten Markdown artikel (TOAST UI Viewer). */
export default function MarkdownViewer({ content }) {
    const host = useRef(null);

    useEffect(() => {
        const viewer = new Viewer({ el: host.current, initialValue: content ?? '' });
        return () => viewer.destroy();
    }, [content]);

    return <div ref={host} className="article-body" />;
}
