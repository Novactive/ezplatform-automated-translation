(function () {
    let form = document.querySelector('#add-translation-modal form[name=add-translation]');
    let targetSelect = form.querySelector('.target-language')
    let container = form.querySelector('.ezautomatedtranslation-services-container')
    let serviceSelector = form.querySelector('#add-translation_translatorAlias')
    let error = container.querySelector('.ezautomatedtranslation-error')

    targetSelect.addEventListener("click", (e) => {
        error.classList.add("invisible");
    });

    form.addEventListener("submit", (e) => {
        let targetLangSelect = form.querySelector("select[name=add-translation\\[language\\]]");
        let sourceLangSelect = form.querySelector("select[name=add-translation\\[base_language\\]]");
        let targetLang = targetLangSelect.value;
        let sourceLang = sourceLangSelect.value;
        let mapping = container.dataset.languagesMapping;
        let serviceAlias = serviceSelector.value;
        if (serviceSelector.getAttribute('type') === "checkbox" && serviceSelector.getAttribute('checked') === 'checked') {
            serviceAlias = '';
        }

        if (!serviceAlias.length) {
            return true;
        }

        let mappingForServiceAlias = mapping[serviceAlias]
        let translationAvailable = sourceLang.includes(mappingForServiceAlias) && targetLang.includes(mappingForServiceAlias);
        if (false === translationAvailable) {
            error.classList.remove("invisible");
            e.preventDefault();
            return false;
        }
        return true;
    });
});
