(function() {
    'use strict';

    // Wait for Swagger UI to be fully loaded
    const waitForSwagger = setInterval(function() {
        if (window.ui && document.querySelector('.swagger-ui')) {
            clearInterval(waitForSwagger);
            initFormPlugin();
        }
    }, 500);

    function initFormPlugin() {
        // Observer to detect when "Try it out" is clicked
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        // Look for the execute button which indicates try-it-out mode
                        const executeBtn = node.querySelector ? node.querySelector('.execute-wrapper') : null;
                        if (executeBtn) {
                            setTimeout(transformJsonToForm, 100);
                        }
                        // Also check for body parameter sections
                        const bodyParams = node.querySelectorAll ? node.querySelectorAll('.body-param') : [];
                        if (bodyParams.length > 0) {
                            setTimeout(transformJsonToForm, 100);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Also handle click events on "Try it out" buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('try-out__btn') ||
                e.target.closest('.try-out__btn')) {
                setTimeout(transformJsonToForm, 300);
            }
        });
    }

    function transformJsonToForm() {
        const textareas = document.querySelectorAll('.body-param textarea');

        textareas.forEach(function(textarea) {
            // Skip if already transformed
            if (textarea.dataset.formified === 'true') return;

            const wrapper = textarea.closest('.body-param');
            if (!wrapper) return;

            // Try to parse the JSON example
            let jsonData;
            try {
                jsonData = JSON.parse(textarea.value);
            } catch (e) {
                return; // Not valid JSON, skip
            }

            // Create form container
            const formContainer = document.createElement('div');
            formContainer.className = 'swagger-form-container';

            // Build form fields from JSON
            const fields = buildFormFields(jsonData, '');
            formContainer.innerHTML = fields;

            // Hide original textarea but keep it for submission
            textarea.style.display = 'none';
            textarea.dataset.formified = 'true';

            // Insert form before textarea
            textarea.parentNode.insertBefore(formContainer, textarea);

            // Add event listeners to update textarea on input
            formContainer.querySelectorAll('input, select, textarea.form-textarea').forEach(function(input) {
                input.addEventListener('input', function() {
                    updateTextareaFromForm(formContainer, textarea);
                });
                input.addEventListener('change', function() {
                    updateTextareaFromForm(formContainer, textarea);
                });
            });

            // Handle array item add/remove
            formContainer.querySelectorAll('.add-array-item').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    addArrayItem(btn);
                    updateTextareaFromForm(formContainer, textarea);
                });
            });

            // Initial sync to ensure values are set
            updateTextareaFromForm(formContainer, textarea);
        });
    }

    function buildFormFields(data, prefix, level = 0) {
        let html = '';

        if (Array.isArray(data)) {
            html += buildArrayFields(data, prefix, level);
        } else if (typeof data === 'object' && data !== null) {
            html += buildObjectFields(data, prefix, level);
        }

        return html;
    }

    function buildObjectFields(obj, prefix, level) {
        let html = '<div class="form-object' + (level > 0 ? ' nested' : '') + '">';

        for (const [key, value] of Object.entries(obj)) {
            const fieldName = prefix ? `${prefix}.${key}` : key;
            const fieldId = fieldName.replace(/\./g, '_').replace(/\[/g, '_').replace(/\]/g, '');

            html += '<div class="form-field">';
            html += `<label for="${fieldId}">${formatLabel(key)}</label>`;

            if (Array.isArray(value)) {
                html += buildArrayFields(value, fieldName, level + 1);
            } else if (typeof value === 'object' && value !== null) {
                html += buildFormFields(value, fieldName, level + 1);
            } else {
                html += buildInputField(fieldId, fieldName, value);
            }

            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function buildArrayFields(arr, prefix, level) {
        let html = `<div class="form-array" data-prefix="${prefix}">`;
        html += `<div class="array-items">`;

        arr.forEach((item, index) => {
            html += `<div class="array-item" data-index="${index}">`;
            html += `<div class="array-item-header">`;
            html += `<span class="item-number">Item ${index + 1}</span>`;
            html += `<button type="button" class="remove-array-item" onclick="removeArrayItem(this)">Supprimer</button>`;
            html += `</div>`;

            if (typeof item === 'object' && item !== null) {
                html += buildObjectFields(item, `${prefix}[${index}]`, level + 1);
            } else {
                const fieldId = `${prefix}_${index}`.replace(/\./g, '_').replace(/\[/g, '_').replace(/\]/g, '');
                html += buildInputField(fieldId, `${prefix}[${index}]`, item);
            }

            html += '</div>';
        });

        html += '</div>';
        html += `<button type="button" class="add-array-item">+ Ajouter un item</button>`;
        html += '</div>';

        return html;
    }

    function buildInputField(id, name, value) {
        const type = typeof value;

        // Check for specific field types based on name
        const lowerName = name.toLowerCase();

        if (type === 'boolean' || value === true || value === false) {
            return `
                <select id="${id}" data-field="${name}" class="form-select">
                    <option value="true" ${value === true ? 'selected' : ''}>Oui</option>
                    <option value="false" ${value === false ? 'selected' : ''}>Non</option>
                </select>
            `;
        }

        // Category dropdown
        if (lowerName.includes('category')) {
            return `
                <select id="${id}" data-field="${name}" class="form-select">
                    <option value="Boissons chaudes" ${value === 'Boissons chaudes' ? 'selected' : ''}>Boissons chaudes</option>
                    <option value="Boissons froides" ${value === 'Boissons froides' ? 'selected' : ''}>Boissons froides</option>
                    <option value="Viennoiseries" ${value === 'Viennoiseries' ? 'selected' : ''}>Viennoiseries</option>
                    <option value="Pâtisseries" ${value === 'Pâtisseries' ? 'selected' : ''}>Pâtisseries</option>
                    <option value="Snacks" ${value === 'Snacks' ? 'selected' : ''}>Snacks</option>
                    <option value="Plats" ${value === 'Plats' ? 'selected' : ''}>Plats</option>
                </select>
            `;
        }

        // Status dropdown
        if (lowerName.includes('status')) {
            return `
                <select id="${id}" data-field="${name}" class="form-select">
                    <option value="pending" ${value === 'pending' ? 'selected' : ''}>En attente</option>
                    <option value="confirmed" ${value === 'confirmed' ? 'selected' : ''}>Confirmée</option>
                    <option value="preparing" ${value === 'preparing' ? 'selected' : ''}>En préparation</option>
                    <option value="ready" ${value === 'ready' ? 'selected' : ''}>Prête</option>
                    <option value="delivered" ${value === 'delivered' ? 'selected' : ''}>Livrée</option>
                    <option value="cancelled" ${value === 'cancelled' ? 'selected' : ''}>Annulée</option>
                </select>
            `;
        }

        // Email field
        if (lowerName.includes('email')) {
            return `<input type="email" id="${id}" data-field="${name}" value="${escapeHtml(value)}" class="form-input" placeholder="email@example.com">`;
        }

        // Password field
        if (lowerName.includes('password')) {
            return `<input type="password" id="${id}" data-field="${name}" value="${escapeHtml(value)}" class="form-input">`;
        }

        // Phone field
        if (lowerName.includes('phone')) {
            return `<input type="tel" id="${id}" data-field="${name}" value="${escapeHtml(value || '')}" class="form-input" placeholder="+33612345678">`;
        }

        // Price field
        if (lowerName.includes('price')) {
            return `<input type="text" id="${id}" data-field="${name}" value="${escapeHtml(value)}" class="form-input" pattern="^\\d+\\.\\d{2}$" placeholder="0.00">`;
        }

        // URL field
        if (lowerName.includes('url') || lowerName.includes('image')) {
            return `<input type="url" id="${id}" data-field="${name}" value="${escapeHtml(value || '')}" class="form-input" placeholder="https://">`;
        }

        // Quantity field
        if (lowerName.includes('quantity')) {
            return `<input type="number" id="${id}" data-field="${name}" value="${value}" class="form-input" min="1">`;
        }

        // Description or notes (textarea)
        if (lowerName.includes('description') || lowerName.includes('notes') || lowerName.includes('instruction')) {
            return `<textarea id="${id}" data-field="${name}" class="form-textarea" rows="3">${escapeHtml(value || '')}</textarea>`;
        }

        // Number
        if (type === 'number') {
            return `<input type="number" id="${id}" data-field="${name}" value="${value}" class="form-input">`;
        }

        // Default text input
        return `<input type="text" id="${id}" data-field="${name}" value="${escapeHtml(value || '')}" class="form-input">`;
    }

    function formatLabel(key) {
        // Convert camelCase to Title Case with spaces
        const formatted = key
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, str => str.toUpperCase())
            .trim();

        // French translations for common fields
        const translations = {
            'Email': 'Email',
            'Plain Password': 'Mot de passe',
            'Password': 'Mot de passe',
            'First Name': 'Prénom',
            'Last Name': 'Nom',
            'Phone': 'Téléphone',
            'Name': 'Nom',
            'Description': 'Description',
            'Price': 'Prix',
            'Category': 'Catégorie',
            'Available': 'Disponible',
            'Ala Carte': 'À la carte',
            'Image Url': 'URL de l\'image',
            'Product': 'Produit',
            'Quantity': 'Quantité',
            'Special Instructions': 'Instructions spéciales',
            'Items': 'Articles',
            'Notes': 'Notes',
            'Table Number': 'Numéro de table',
            'Status': 'Statut'
        };

        return translations[formatted] || formatted;
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function updateTextareaFromForm(formContainer, textarea) {
        const result = {};

        // Get all inputs
        const inputs = formContainer.querySelectorAll('input, select, textarea.form-textarea');

        inputs.forEach(function(input) {
            const fieldPath = input.dataset.field;
            if (!fieldPath) return;

            let value = input.value;

            // Convert types
            if (input.tagName === 'SELECT' && (value === 'true' || value === 'false')) {
                value = value === 'true';
            } else if (input.type === 'number') {
                value = value === '' ? 0 : Number(value);
            }

            setNestedValue(result, fieldPath, value);
        });

        const newValue = JSON.stringify(result, null, 2);

        // Update textarea value using native setter to trigger React
        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLTextAreaElement.prototype, 'value').set;
        nativeInputValueSetter.call(textarea, newValue);

        // Dispatch events to notify React of the change
        const inputEvent = new Event('input', { bubbles: true, cancelable: true });
        textarea.dispatchEvent(inputEvent);

        const changeEvent = new Event('change', { bubbles: true, cancelable: true });
        textarea.dispatchEvent(changeEvent);
    }

    function setNestedValue(obj, path, value) {
        const parts = path.replace(/\]/g, '').split(/[\.\[]/);
        let current = obj;

        for (let i = 0; i < parts.length - 1; i++) {
            const part = parts[i];
            const nextPart = parts[i + 1];
            const isNextArray = !isNaN(parseInt(nextPart));

            if (!(part in current)) {
                current[part] = isNextArray ? [] : {};
            }
            current = current[part];
        }

        const lastPart = parts[parts.length - 1];
        current[lastPart] = value;
    }

    // Global function for removing array items
    window.removeArrayItem = function(btn) {
        const arrayItem = btn.closest('.array-item');
        const arrayContainer = btn.closest('.form-array');
        const formContainer = btn.closest('.swagger-form-container');
        const textarea = formContainer.nextElementSibling;

        arrayItem.remove();

        // Re-index items
        const items = arrayContainer.querySelectorAll('.array-item');
        items.forEach((item, index) => {
            item.dataset.index = index;
            item.querySelector('.item-number').textContent = `Item ${index + 1}`;

            // Update field names
            item.querySelectorAll('[data-field]').forEach(input => {
                const oldField = input.dataset.field;
                input.dataset.field = oldField.replace(/\[\d+\]/, `[${index}]`);
            });
        });

        updateTextareaFromForm(formContainer, textarea);
    };

    function addArrayItem(btn) {
        const arrayContainer = btn.closest('.form-array');
        const arrayItems = arrayContainer.querySelector('.array-items');
        const prefix = arrayContainer.dataset.prefix;

        // Get existing items to clone structure
        const existingItem = arrayItems.querySelector('.array-item');
        if (!existingItem) return;

        const newIndex = arrayItems.querySelectorAll('.array-item').length;
        const newItem = existingItem.cloneNode(true);

        newItem.dataset.index = newIndex;
        newItem.querySelector('.item-number').textContent = `Item ${newIndex + 1}`;

        // Update field names and clear values
        newItem.querySelectorAll('[data-field]').forEach(input => {
            const oldField = input.dataset.field;
            input.dataset.field = oldField.replace(/\[\d+\]/, `[${newIndex}]`);

            // Reset values based on type
            if (input.type === 'number') {
                input.value = input.min || 1;
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });

        arrayItems.appendChild(newItem);

        // Add event listeners to new inputs
        const formContainer = btn.closest('.swagger-form-container');
        const textarea = formContainer.nextElementSibling;

        newItem.querySelectorAll('input, select, textarea.form-textarea').forEach(input => {
            input.addEventListener('input', () => updateTextareaFromForm(formContainer, textarea));
            input.addEventListener('change', () => updateTextareaFromForm(formContainer, textarea));
        });
    }
})();
