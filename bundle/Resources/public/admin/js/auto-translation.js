import SelectedLocationsComponent from './components/selected.locations.component.js';
const { ibexa } = window;

(function (global, doc) {
    const udwContainer = doc.querySelector('#react-udw');
    const formSearch = doc.querySelector('form[name="auto_translation_actions_search"]');
    const sortableColumns = doc.querySelectorAll('.ibexa-table__sort-column');
    const sortedActiveField = doc.querySelector('#auto_translation_actions_search_sort_field').value;
    const sortedActiveDirection = doc.querySelector('#auto_translation_actions_search_sort_direction').value;
    const sortField = doc.querySelector('#auto_translation_actions_search_sort_field');
    const sortDirection = doc.querySelector('#auto_translation_actions_search_sort_direction');
    const CLASS_SORTED_ASC = 'ibexa-table__sort-column--asc';
    const CLASS_SORTED_DESC = 'ibexa-table__sort-column--desc';

    const closeUDW = () => ReactDOM.unmountComponentAtNode(udwContainer);
    const selectLocationBut = doc.querySelector('.js-auto_translation-select-location-id');
    function notify(message, type = 'info') {
        if (!message) return;
        const eventInfo = new CustomEvent('ibexa-notify', {
            detail: {
                label: type,
                message: message
            }
        });
        document.body.dispatchEvent(eventInfo);
    }
    if (selectLocationBut) {
        let udwRoot = null;

        selectLocationBut.addEventListener('click', function (e) {
            e.preventDefault();
            const clickedButton = e.target;
            const config = JSON.parse(e.currentTarget.dataset.udwConfig);
            const selectedLocationList = doc.querySelector(clickedButton.dataset.selectedLocationListSelector);
            ReactDOM.render(React.createElement(ibexa.modules.UniversalDiscovery, {
                onConfirm: (data) => {
                        ReactDOM.render(React.createElement(SelectedLocationsComponent, {
                            items: data,
                            onDelete: (locations) => {
                                doc.querySelector(clickedButton.dataset.locationInputSelector).value = locations.map(location => location.id).join();
                            }
                        }), selectedLocationList);

                     doc.querySelector(clickedButton.dataset.locationInputSelector).value = data.map(location => location.id).join();
                    closeUDW();
                    const formError = clickedButton.closest('.auto-translation-field--ezobjectrelationlist')
                        .querySelector('.ibexa-form-error');
                   if(formError) {
                       formError.innerHTML = '';
                   }
                },
                onCancel: () => {
                    closeUDW();
                },
                ...config
            }), udwContainer);
        });
    }
    const sortItems = (event) => {
        const { target } = event;
        const { field, direction } = target.dataset;

        sortField.value = field;
        target.dataset.direction = direction === 'ASC' ? 'DESC' : 'ASC';
        sortDirection.setAttribute('value', direction === 'DESC' ? 1 : 0);
        formSearch.submit();
    };

    const setSortedClass = () => {
        doc.querySelectorAll('.ibexa-table__sort-column').forEach((node) => {
            node.classList.remove(CLASS_SORTED_ASC, CLASS_SORTED_DESC);
        });

        if (sortedActiveField) {
            const sortedFieldNode = doc.querySelector(`.ibexa-table__sort-column--${sortedActiveField}`);

            if (!sortedFieldNode) {
                return;
            }

            if (parseInt(sortedActiveDirection, 10) === 1) {
                sortedFieldNode.classList.add(CLASS_SORTED_ASC);
            } else {
                sortedFieldNode.classList.add(CLASS_SORTED_DESC);
            }
        }
    };

    setSortedClass();
    sortableColumns.forEach((column) => column.addEventListener('click', sortItems, false));

})(window, document);
