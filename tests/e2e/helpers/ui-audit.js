import { expect } from '@playwright/test';

const MIN_CLICK_TARGET = 32;
const MIN_CONTRAST_RATIO = 4.5;

export async function expectNoHorizontalOverflow(page) {
    const overflow = await page.evaluate(() => ({
        clientWidth: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
    }));

    expect(overflow.scrollWidth, `Horizontal overflow: ${overflow.scrollWidth}px > ${overflow.clientWidth}px`).toBeLessThanOrEqual(overflow.clientWidth + 1);
}

export async function expectNoCriticalOverlap(page, selectors) {
    const overlaps = await page.evaluate((targetSelectors) => {
        const getRect = (element) => {
            const rect = element.getBoundingClientRect();

            return {
                top: rect.top,
                right: rect.right,
                bottom: rect.bottom,
                left: rect.left,
                width: rect.width,
                height: rect.height,
            };
        };

        const isVisible = (element) => {
            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();

            return style.visibility !== 'hidden'
                && style.display !== 'none'
                && Number(style.opacity) > 0
                && rect.width > 0
                && rect.height > 0;
        };

        const intersects = (a, b) => {
            const width = Math.min(a.right, b.right) - Math.max(a.left, b.left);
            const height = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);

            return width > 2 && height > 2;
        };

        const elements = targetSelectors
            .flatMap((selector) => Array.from(document.querySelectorAll(selector)).map((element) => ({
                selector,
                text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 80),
                rect: getRect(element),
                element,
            })))
            .filter((item) => isVisible(item.element));

        const issues = [];

        for (let index = 0; index < elements.length; index += 1) {
            for (let compareIndex = index + 1; compareIndex < elements.length; compareIndex += 1) {
                const first = elements[index];
                const second = elements[compareIndex];

                if (first.element.contains(second.element) || second.element.contains(first.element)) {
                    continue;
                }

                if (intersects(first.rect, second.rect)) {
                    issues.push(`${first.selector} "${first.text}" overlaps ${second.selector} "${second.text}"`);
                }
            }
        }

        return issues;
    }, selectors);

    expect.soft(overlaps, `Critical UI overlap found:\n${overlaps.join('\n')}`).toEqual([]);
}

export async function expectReadableTextContrast(page, selector) {
    const issues = await page.evaluate(({ selector: targetSelector, minContrastRatio }) => {
        const parseColor = (value) => {
            const match = value.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)/);

            if (!match) {
                return null;
            }

            return {
                r: Number(match[1]),
                g: Number(match[2]),
                b: Number(match[3]),
                a: match[4] === undefined ? 1 : Number(match[4]),
            };
        };

        const luminance = ({ r, g, b }) => {
            const channel = [r, g, b].map((value) => {
                const normalized = value / 255;

                return normalized <= 0.03928
                    ? normalized / 12.92
                    : ((normalized + 0.055) / 1.055) ** 2.4;
            });

            return 0.2126 * channel[0] + 0.7152 * channel[1] + 0.0722 * channel[2];
        };

        const contrast = (first, second) => {
            const lighter = Math.max(luminance(first), luminance(second));
            const darker = Math.min(luminance(first), luminance(second));

            return (lighter + 0.05) / (darker + 0.05);
        };

        const getEffectiveBackground = (element) => {
            let current = element;

            while (current && current !== document.documentElement) {
                const style = window.getComputedStyle(current);

                if (style.backgroundImage && style.backgroundImage !== 'none') {
                    return null;
                }

                const color = parseColor(style.backgroundColor);

                if (color && color.a > 0.95) {
                    return color;
                }

                current = current.parentElement;
            }

            return parseColor(window.getComputedStyle(document.body).backgroundColor) || { r: 255, g: 255, b: 255, a: 1 };
        };

        return Array.from(document.querySelectorAll(targetSelector))
            .filter((element) => {
                const rect = element.getBoundingClientRect();
                const style = window.getComputedStyle(element);

                return element.textContent.trim().length > 0
                    && rect.width > 0
                    && rect.height > 0
                    && style.visibility !== 'hidden'
                    && style.display !== 'none';
            })
            .map((element) => {
                const foreground = parseColor(window.getComputedStyle(element).color);
                const background = getEffectiveBackground(element);

                if (!foreground || !background) {
                    return null;
                }

                return {
                    text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 80),
                    ratio: Number(contrast(foreground, background).toFixed(2)),
                };
            })
            .filter(Boolean)
            .filter((item) => item.ratio < minContrastRatio);
    }, { selector, minContrastRatio: MIN_CONTRAST_RATIO });

    expect.soft(issues, `Low text contrast found:\n${issues.map((issue) => `${issue.ratio}: ${issue.text}`).join('\n')}`).toEqual([]);
}

export async function expectClickTargetsUsable(page, selector) {
    const issues = await page.evaluate(({ selector: targetSelector, minClickTarget }) => {
        return Array.from(document.querySelectorAll(targetSelector))
            .filter((element) => {
                const rect = element.getBoundingClientRect();
                const style = window.getComputedStyle(element);

                return rect.width > 0
                    && rect.height > 0
                    && style.visibility !== 'hidden'
                    && style.display !== 'none';
            })
            .map((element) => {
                const rect = element.getBoundingClientRect();

                return {
                    text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 80) || element.getAttribute('aria-label') || element.tagName,
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                };
            })
            .filter((item) => item.width < minClickTarget || item.height < minClickTarget);
    }, { selector, minClickTarget: MIN_CLICK_TARGET });

    expect.soft(issues, `Small click target found:\n${issues.map((issue) => `${issue.width}x${issue.height}: ${issue.text}`).join('\n')}`).toEqual([]);
}

export async function expectCopywritingQuality(page, texts) {
    for (const text of texts.required) {
        const visibleCount = await page.getByText(text, { exact: false }).evaluateAll((elements) => {
            return elements.filter((element) => {
                const rect = element.getBoundingClientRect();
                const style = window.getComputedStyle(element);

                return rect.width > 0
                    && rect.height > 0
                    && style.visibility !== 'hidden'
                    && style.display !== 'none';
            }).length;
        });

        expect.soft(visibleCount, `Required copy is not visible: ${text}`).toBeGreaterThan(0);
    }

    const repeatedTexts = await page.evaluate(() => {
        const ignored = new Set(['Jiwa', 'Uang', 'Beras']);
        const counts = new Map();

        Array.from(document.body.querySelectorAll('h1, h2, h3, h4, p, button, a, span'))
            .map((element) => element.textContent.trim().replace(/\s+/g, ' '))
            .filter((text) => text.length >= 10 && !ignored.has(text))
            .filter((text) => /[A-Za-zÀ-ÿ]/.test(text))
            .filter((text) => {
                const letters = text.match(/[A-Za-zÀ-ÿ]/g) || [];

                return letters.length / text.length >= 0.5;
            })
            .forEach((text) => counts.set(text, (counts.get(text) || 0) + 1));

        return Array.from(counts.entries())
            .filter(([, count]) => count > 3)
            .map(([text, count]) => `${count}x: ${text}`);
    });

    expect.soft(repeatedTexts, `Excessive repeated copy found:\n${repeatedTexts.join('\n')}`).toEqual([]);
}
