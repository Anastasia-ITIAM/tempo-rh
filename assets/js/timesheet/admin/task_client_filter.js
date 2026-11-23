document.addEventListener('DOMContentLoaded', () => {
    const clientSelect = document.querySelector('.js-client-filter');
    const projectSelect = document.querySelector('.js-project-select');

    if (!clientSelect || !projectSelect) {
        return;
    }

    const allProjects = Array.from(projectSelect.options)
        .filter((option) => option.value)
        .map((option) => ({
            value: option.value,
            text: option.textContent,
            clientId: option.dataset.clientId,
        }));

    function applyFilter() {
        const tomSelect = projectSelect.tomselect;

        if (!tomSelect) {
            setTimeout(applyFilter, 100);
            return;
        }

        const clientId = clientSelect.value;

        tomSelect.clear();
        tomSelect.clearOptions();

        if (!clientId) {
            tomSelect.disable();
            return;
        }

        tomSelect.enable();

        allProjects
            .filter((project) => project.clientId === clientId)
            .forEach((project) => {
                tomSelect.addOption({ value: project.value, text: project.text });
            });

        tomSelect.refreshOptions(false);
    }

    clientSelect.addEventListener('change', applyFilter);
    applyFilter();
});