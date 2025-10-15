(function (global, doc, ibexa) {
    const SELECTOR_FIELD = '.auto-translation-field--ezselection';
    const SELECTOR_SELECTED = '.ibexa-dropdown__selection-info';
    const SELECTOR_ERROR_NODE = '.ibexa-form-error';
    const EVENT_VALUE_CHANGED = 'change';

    class AutoTranslationSelectionValidator extends ibexa.BaseFieldValidator {
        /**
         * Validates the textarea field value
         *
         * @method validateInput
         * @param {Event} event
         * @returns {Object}
         * @memberof EzSelectionValidator
         */
        validateInput(event) {
            const fieldContainer = event.currentTarget.closest(SELECTOR_FIELD);
            const selection = fieldContainer.querySelector('.ibexa-data-source__input');
            const hasSelectedOptions = !!selection.value;
            const isRequired = selection && selection.required;
            const isError = isRequired && !hasSelectedOptions;
            const label = fieldContainer.querySelector('.ibexa-label').innerHTML;
            const errorMessage = ibexa.errors.emptyField.replace('{fieldName}', label);

            return {
                isError,
                errorMessage,
            };
        }
    }

    const validator = new AutoTranslationSelectionValidator({
        classInvalid: 'is-invalid',
        fieldSelector: SELECTOR_FIELD,
        eventsMap: [
            {
                selector: `${SELECTOR_FIELD} .ibexa-data-source__input--selection`,
                eventName: EVENT_VALUE_CHANGED,
                callback: 'validateInput',
                errorNodeSelectors: [SELECTOR_ERROR_NODE],
                invalidStateSelectors: [SELECTOR_SELECTED],
            },
        ],
    });

    validator.init();

    ibexa.addConfig('fieldTypeValidators', [validator], true);
})(window, window.document, window.ibexa);
