import React from 'react';

interface BrandLogoProps extends React.SVGProps<SVGSVGElement> {
    variant?: 'full' | 'symbol';
    symbolColor?: string;
    textColor?: string;
    className?: string;
}

/**
 * Komikini Official Brand Logo
 * 
 * Concept A: 3-Koma Action Panels
 * - Symbol: Vertical comic strip column + 2 dynamic 45° action manga panels (#bdd600)
 * - Wordmark: Pure geometric vector paths (#f3f3ef)
 */
export const BrandLogo: React.FC<BrandLogoProps> = ({
    variant = 'full',
    symbolColor = '#bdd600',
    textColor = '#f3f3ef',
    className = 'h-7 w-auto',
    ...props
}) => {
    if (variant === 'symbol') {
        return (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 256 256"
                className={className}
                fill="none"
                role="img"
                aria-label="Komikini Symbol"
                {...props}
            >
                <path
                    fill={symbolColor}
                    fillRule="evenodd"
                    d="M 36 28 H 88 V 228 H 36 Z M 104 88 L 164 28 H 220 V 56 L 160 116 H 104 Z M 104 140 H 160 L 220 200 V 228 H 164 L 104 168 Z"
                />
            </svg>
        );
    }

    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 776 256"
            className={className}
            fill="none"
            role="img"
            aria-label="Komikini Logo"
            {...props}
        >
            {/* 3-Koma Symbol (Scaled and aligned) */}
            <g id="symbol">
                <path
                    fill={symbolColor}
                    fillRule="evenodd"
                    d="M 61 53 H 100 V 203 H 61 Z M 112 98 L 157 53 H 199 V 74 L 154 119 H 112 Z M 112 137 H 154 L 199 182 V 203 H 157 L 112 158 Z"
                />
            </g>
            {/* Bespoke Geometric Wordmark KOMIKINI */}
            <g id="wordmark">
                <path
                    fill={textColor}
                    fillRule="evenodd"
                    d="M 246 88 H 264 V 168 H 246 Z M 272 120 L 286 88 H 302 V 104 L 288 120 Z M 272 136 H 288 L 302 152 V 168 H 286 Z M 318 88 H 378 V 168 H 318 Z M 336 106 V 150 H 360 V 106 Z M 394 88 H 412 V 168 H 394 Z M 448 88 H 466 V 168 H 448 Z M 412 88 L 430 124 L 448 88 H 436 L 430 112 L 424 88 Z M 482 88 H 500 V 168 H 482 Z M 516 88 H 534 V 168 H 516 Z M 542 120 L 556 88 H 572 V 104 L 558 120 Z M 542 136 H 558 L 572 152 V 168 H 556 Z M 588 88 H 606 V 168 H 588 Z M 622 88 H 640 V 168 H 622 Z M 664 88 H 682 V 168 H 664 Z M 640 88 H 652 L 664 156 V 168 H 652 L 640 100 Z M 698 88 H 716 V 168 H 698 Z"
                />
            </g>
        </svg>
    );
};

export default BrandLogo;
