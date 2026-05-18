export const parseJsonScript = (id) => {
    const element = document.getElementById(id);

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error(`Failed to parse JSON config from #${id}`, error);
        return null;
    }
};

export const loadPublicHomeConfig = () => parseJsonScript('public-home-config');
