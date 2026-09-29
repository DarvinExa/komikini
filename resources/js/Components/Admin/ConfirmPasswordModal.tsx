import React, { useState, useEffect } from 'react';

interface ConfirmPasswordModalProps {
    isOpen: boolean;
    title: string;
    description: string;
    confirmText?: string;
    confirmVariant?: 'primary' | 'danger';
    errorMessage?: string | null;
    isProcessing?: boolean;
    onClose: () => void;
    onConfirm: (password: string) => void;
}

export default function ConfirmPasswordModal({
    isOpen,
    title,
    description,
    confirmText = 'Konfirmasi Tindakan',
    confirmVariant = 'danger',
    errorMessage,
    isProcessing = false,
    onClose,
    onConfirm,
}: ConfirmPasswordModalProps) {
    const [password, setPassword] = useState('');

    useEffect(() => {
        if (isOpen) {
            setPassword('');
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!password.trim() || isProcessing) return;
        onConfirm(password);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-none">
            <div
                className="w-full max-w-md bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none animate-in fade-in zoom-in-95 duration-100"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-headline"
            >
                <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4">
                    <h3 id="modal-headline" className="font-display text-lg tracking-wider uppercase text-[#F8F8F8]">
                        {title}
                    </h3>
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={isProcessing}
                        className="text-[#888888] hover:text-white px-2 py-1 text-sm font-mono border border-transparent hover:border-[#444444] rounded-none"
                    >
                        ESC / [X]
                    </button>
                </div>

                <p className="text-sm text-[#AAAAAA] mb-5 leading-relaxed font-sans">
                    {description}
                </p>

                {errorMessage && (
                    <div className="mb-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono">
                        {errorMessage}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                            Kata Sandi Anda (Verifikasi Otorisasi)
                        </label>
                        <input
                            type="password"
                            required
                            autoFocus
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Masukkan kata sandi akun..."
                            disabled={isProcessing}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-sm font-sans rounded-none transition-colors"
                        />
                    </div>

                    <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#262626]">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={isProcessing}
                            className="px-4 py-2 text-xs font-display tracking-wider uppercase text-[#AAAAAA] hover:text-white bg-[#222222] hover:bg-[#2A2A2A] border border-[#333333] transition-colors rounded-none"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isProcessing || !password.trim()}
                            className={`px-4 py-2 text-xs font-display tracking-wider uppercase font-bold transition-colors disabled:opacity-50 rounded-none ${
                                confirmVariant === 'danger'
                                    ? 'bg-[#E56458] text-white hover:bg-[#d44f43] border border-[#E56458]'
                                    : 'bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] border border-[#BAD306]'
                            }`}
                        >
                            {isProcessing ? 'Memproses...' : confirmText}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
