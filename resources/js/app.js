function initSearchableSelect(target, config = {}) {
    const select = typeof target === 'string' ? document.querySelector(target) : target;

    if (!select || select.dataset.searchableReady === 'true') {
        return null;
    }

    select.dataset.searchableReady = 'true';
    select.classList.add('searchable-select-native');

    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select';

    const input = document.createElement('input');
    input.type = 'text';
    input.autocomplete = 'off';
    input.className = config.inputClass || 'form-input';
    input.placeholder = config.placeholder || select.options[0]?.textContent?.trim() || 'Cari pilihan';

    const dropdown = document.createElement('div');
    dropdown.className = 'searchable-select-menu';

    select.insertAdjacentElement('afterend', wrapper);
    wrapper.append(input, dropdown);

    let activeIndex = -1;
    let currentItems = [];

    function optionItems() {
        return Array.from(select.options).map((option) => ({
            value: option.value,
            label: option.textContent.trim(),
            isPlaceholder: option.value === '',
        }));
    }

    function selectedLabel() {
        const selected = select.selectedOptions[0];

        return selected && selected.value !== '' ? selected.textContent.trim() : '';
    }

    function sync() {
        input.disabled = select.disabled;
        input.value = selectedLabel();
        input.placeholder = config.placeholder || select.options[0]?.textContent?.trim() || 'Cari pilihan';

        if (select.disabled) {
            close();
        }
    }

    function close() {
        dropdown.classList.remove('open');
        dropdown.innerHTML = '';
        activeIndex = -1;
        currentItems = [];
    }

    function choose(item) {
        select.value = item.value;
        input.value = item.isPlaceholder ? '' : item.label;
        close();
        select.dispatchEvent(new Event('change', { bubbles: true }));

        if (typeof config.onSelect === 'function') {
            config.onSelect(select.value, item);
        }
    }

    function render(query = '') {
        if (select.disabled) {
            close();
            return;
        }

        const normalizedQuery = query.trim().toLowerCase();
        const items = optionItems().filter((item) => {
            if (item.isPlaceholder) {
                return normalizedQuery === '';
            }

            return item.label.toLowerCase().includes(normalizedQuery);
        });

        currentItems = items.slice(0, config.maxResults || 80);
        activeIndex = currentItems.length > 0 ? 0 : -1;
        dropdown.innerHTML = '';

        if (currentItems.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = config.emptyText || 'Data tidak ditemukan';
            dropdown.appendChild(empty);
        } else {
            currentItems.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'searchable-select-option';
                button.textContent = item.isPlaceholder ? item.label : item.label;
                button.dataset.active = index === activeIndex ? 'true' : 'false';
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    choose(item);
                });
                dropdown.appendChild(button);
            });
        }

        dropdown.classList.add('open');
    }

    function moveActive(step) {
        if (currentItems.length === 0) {
            return;
        }

        activeIndex = (activeIndex + step + currentItems.length) % currentItems.length;
        dropdown.querySelectorAll('.searchable-select-option').forEach((option, index) => {
            option.dataset.active = index === activeIndex ? 'true' : 'false';
        });
    }

    input.addEventListener('focus', () => render(input.value === selectedLabel() ? '' : input.value));
    input.addEventListener('input', () => render(input.value));
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!dropdown.classList.contains('open')) {
                render(input.value);
            } else {
                moveActive(1);
            }
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveActive(-1);
        }

        if (event.key === 'Enter' && dropdown.classList.contains('open')) {
            event.preventDefault();
            const item = currentItems[activeIndex] || currentItems[0];
            if (item) {
                choose(item);
            }
        }

        if (event.key === 'Escape') {
            close();
            input.value = selectedLabel();
        }
    });
    input.addEventListener('blur', () => {
        window.setTimeout(() => {
            close();
            input.value = selectedLabel();
        }, 120);
    });
    select.addEventListener('searchable:refresh', sync);

    const observer = new MutationObserver(sync);
    observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });

    sync();

    return { select, input, wrapper, refresh: sync };
}

window.initSearchableSelect = initSearchableSelect;
