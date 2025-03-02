/**
 * Custom Dropdown Factory
 * Creates a height-limited dropdown with specified options
 *
 * @param {string} containerId - ID of the container element
 * @param {Object[]} options - Array of dropdown options
 * @param {string} options[].value - Value of the option
 * @param {string} options[].label - Display text of the option
 * @param {Object} config - Configuration options
 * @param {string} config.placeholder - Text to display when nothing is selected
 * @param {string} config.name - Name attribute for the hidden input
 * @param {number} config.maxHeight - Maximum height of dropdown menu in pixels
 * @param {string} config.buttonClass - Additional CSS classes for the button
 * @param {Function} config.onChange - Callback function when selection changes
 */
function createCustomDropdown(containerId, options, config = {}) {
	// Default configuration
	const defaults = {
		placeholder: 'Select an option',
		name: 'selectedValue',
		maxHeight: 200,
		buttonClass: 'btn-outline-secondary',
		onChange: null
	};

	// Merge defaults with provided config
	const settings = {...defaults, ...config};

	// Generate unique IDs for this instance
	const uniqueId = 'dropdown_' + Math.random().toString(36).substring(2, 9);
	const buttonId = 'btn_' + uniqueId;
	const textId = 'text_' + uniqueId;
	const inputId = 'input_' + uniqueId;

	const dropdownHtml = `
				<div class="btn-group w-100">
                    <span class="input-group-text">
                        <i class="bi bi-clock time-icon"></i>
                    </span>
					<button class="btn ${settings.buttonClass} dropdown-toggle w-100 d-flex justify-content-between align-items-center"
							type="button"
							id="${buttonId}"
							data-bs-toggle="dropdown"
							aria-expanded="false">
						<span id="${textId}">${settings.placeholder}</span>
					</button>
					<ul class="dropdown-menu" aria-labelledby="${buttonId}" style="max-height: ${settings.maxHeight}px;">
						${options.map(option =>
			`<li><a class="dropdown-item" href="#" data-value="${option.value}">${option.label}</a></li>`
		).join('')}
					</ul>

					<input type="hidden" id="${inputId}" name="${settings.name}" value="">
				</div>
            `;
	// Create dropdown HTML

	// Insert the dropdown HTML into the container
	$(`#${containerId}`).html(dropdownHtml);

	// Attach event handlers
	$(`#${containerId} .dropdown-item`).on('click', function(e) {
		e.preventDefault();

		const selectedValue = $(this).data('value');
		const selectedText = $(this).text();

		// Update displayed text
		$(`#${textId}`).text(selectedText);

		// Update hidden input value
		$(`#${inputId}`).val(selectedValue);

		// Add 'active' class to selected item and remove from others
		$(`#${containerId} .dropdown-item`).removeClass('active');
		$(this).addClass('active');
		$(`#${containerId} > ul`).removeClass('show');

		// Call onChange callback if provided
		if (typeof settings.onChange === 'function') {
			settings.onChange(selectedValue, selectedText);
		}
	});

	// Return methods to interact with this dropdown
	return {
		getValue: () => $(`#${inputId}`).val(),
		setValue: (value) => {
			const item = $(`#${containerId} .dropdown-item[data-value="${value}"]`);
			if (item.length) {
				item.click();
			}
		},
		getText: () => $(`#${textId}`).text(),
		reset: () => {
			$(`#${textId}`).text(settings.placeholder);
			$(`#${inputId}`).val('');
			$(`#${containerId} .dropdown-item`).removeClass('active');
		}
	};
}

function initCustomDropdown(containerId, inputId, config = {}) {
	// Default configuration
	const defaults = {
		placeholder: 'Select an option',
		onChange: null
	};

	// Merge defaults with provided config
	const settings = {...defaults, ...config};


	// Attach event handlers
	$(`#${containerId} .dropdown-item`).on('click', function(e) {
		e.preventDefault();

		const selectedValue = $(this).data('value');
		const selectedText = $(this).text();

		// Update displayed text
		$(`#${inputId+'-span'}`).text(selectedText);

		// Update hidden input value
		$(`#${inputId}`).val(selectedValue);

		// Add 'active' class to selected item and remove from others
		$(`#${containerId} .dropdown-item`).removeClass('active');
		$(this).addClass('active');
		$(`#${containerId} > ul`).removeClass('show');

		// Call onChange callback if provided
		if (typeof settings.onChange === 'function') {
			settings.onChange(selectedValue, selectedText);
		}
	});

	// Return methods to interact with this dropdown
	return {
		getValue: () => $(`#${inputId}`).val(),
		setValue: (value) => {
			const item = $(`#${containerId} .dropdown-item[data-value="${value}"]`);
			if (item.length) {
				item.click();
			}
		},
		getText: () => $(`#${inputId+'-span'}`).text(),
		reset: () => {
			// $(`#${inputId+'-span'}`).text(settings.placeholder);
			// $(`#${inputId}`).val('');
			$(`#${containerId} .dropdown-item`).removeClass('active');
		}
	};
}

function generateTimeOptions() {
	const options = [];
	for(let h = 0; h < 24; h++) {
		for(let m = 0; m < 60; m += 15) {
			const hour = h.toString().padStart(2, '0');
			const minute = m.toString().padStart(2, '0');
			const timeStr = `${hour}:${minute}`;
			options.push({ value: timeStr, label: timeStr });
		}
	}
	return options;
}
