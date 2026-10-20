const mainContentSelector = "main";
const cashierPage = page => page === "cashier" || page.startsWith("cashier-");
const loadedPageScripts = new Set();
let navigationController;
let pageController = new AbortController();

if (document.body.dataset.pageScript) {
    loadedPageScripts.add(new URL(document.body.dataset.pageScript, location.origin).href);
}

function dispatchPageLoaded(page) {
    pageController.abort();
    pageController = new AbortController();
    document.body.dataset.page = page;
    document.dispatchEvent(new CustomEvent("spa:page-loaded", {
        detail: { page, signal: pageController.signal }
    }));
}

function updateNavigation(page, title) {
    document.title = `${title} · Billing Hospital`;

    const pageTitle = document.getElementById("page-title");
    if (pageTitle) pageTitle.textContent = title;

    document.querySelectorAll("nav a[href*='?page=']").forEach(link => {
        const url = new URL(link.href, location.href);
        const active = url.searchParams.get("page") === page;
        link.setAttribute("aria-current", active ? "page" : "false");
        link.classList.toggle("bg-blue-50", active);
        link.classList.toggle("text-blue-700", active);
        link.classList.toggle("text-slate-600", !active);
        link.classList.toggle("hover:bg-slate-50", !active);
        link.classList.toggle("hover:text-slate-900", !active);

        const icon = link.querySelector("svg");
        if (icon) {
            icon.classList.toggle("text-blue-600", active);
            icon.classList.toggle("text-slate-400", !active);
        }
    });
}

function executeInlineScripts(container) {
    container.querySelectorAll("script:not([src])").forEach(original => {
        const script = document.createElement("script");
        for (const attribute of original.attributes) {
            script.setAttribute(attribute.name, attribute.value);
        }
        script.textContent = original.textContent;
        original.replaceWith(script);
    });
}

async function ensurePageScript(scriptUrl, isModule) {
    if (!scriptUrl) return;
    const url = new URL(scriptUrl, location.origin).href;
    if (loadedPageScripts.has(url)) return;

    if (isModule) {
        await import(url);
    } else {
        await new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = url;
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Could not load page script: ${url}`));
            document.body.append(script);
        });
    }
    loadedPageScripts.add(url);
}

async function loadPage(url, addHistory = true) {
    navigationController?.abort();
    navigationController = new AbortController();
    const { signal } = navigationController;
    const partialUrl = new URL(url.href);
    partialUrl.searchParams.set("partial", "1");

    const response = await fetch(partialUrl, {
        credentials: "same-origin",
        headers: { "X-Requested-With": "SPA" },
        signal
    });
    if (signal.aborted) return;
    if (!response.ok) {
        throw new Error(`Could not open page (${response.status}).`);
    }

    const titleHeader = response.headers.get("X-Page-Title");
    const pageHeader = response.headers.get("X-Page-Id");
    if (!titleHeader || !pageHeader) {
        location.assign(url.href);
        return;
    }

    const main = document.querySelector(mainContentSelector);
    if (!main) {
        location.assign(url.href);
        return;
    }

    main.innerHTML = await response.text();
    executeInlineScripts(main);
    const page = decodeURIComponent(pageHeader);
    const title = decodeURIComponent(titleHeader);
    updateNavigation(page, title);

    const scriptUrl = response.headers.get("X-Page-Script");
    const scriptIsModule = response.headers.get("X-Page-Module") === "1";
    try {
        await ensurePageScript(scriptUrl, scriptIsModule);
    } catch (error) {
        if (signal.aborted) return;
        console.error("Could not load the page script.", error);
        location.assign(url.href);
        return;
    }
    if (signal.aborted) return;

    document.body.dataset.pageScript = scriptUrl || "";
    document.body.dataset.pageModule = scriptIsModule ? "1" : "0";
    if (addHistory) {
        url.searchParams.delete("partial");
        history.pushState({}, "", url.href);
    }

    dispatchPageLoaded(page);
    if (addHistory) window.scrollTo(0, 0);
}

window.refreshCurrentPage = async () => {
    const scrollY = window.scrollY;
    await loadPage(new URL(location.href), false);
    window.scrollTo(0, scrollY);
};
window.refreshAdminList = window.refreshCurrentPage;

document.addEventListener("click", event => {
    const anchor = event.target.closest("a[href]");
    if (!anchor || anchor.target === "_blank" || event.defaultPrevented ||
        event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    const url = new URL(anchor.href, location.href);
    if (url.origin !== location.origin || !url.pathname.endsWith("/index.php")) return;

    const destination = url.searchParams.get("page") || "";
    const currentPage = document.body.dataset.page || "";
    if (!destination || cashierPage(destination) || cashierPage(currentPage)) return;

    event.preventDefault();
    loadPage(url).catch(error => {
        console.error("SPA navigation failed.", error);
        location.assign(url.href);
    });
});

window.addEventListener("popstate", () => {
    const page = new URL(location.href).searchParams.get("page") || "";
    if (!page || cashierPage(page) || cashierPage(document.body.dataset.page || "")) return;

    loadPage(new URL(location.href), false).catch(error => {
        console.error("Could not restore the requested page.", error);
        location.reload();
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const page = document.body.dataset.page || "";
    const title = document.getElementById("page-title")?.textContent.trim() || "";
    if (title) updateNavigation(page, title);
    dispatchPageLoaded(page);
});