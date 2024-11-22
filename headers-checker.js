(function () {
	// Aguarda o DOM do editor estar pronto
	wp.domReady(() => {
		const { registerPlugin } = wp.plugins;
		const { PluginSidebar } = wp.editPost;
		const { Button } = wp.components;
		const { createElement } = wp.element;

		// Registra o plugin com um botão na barra superior
		registerPlugin("headers-checker-sidebar", {
			render: () => {
				return createElement(
					PluginSidebar,
					{
						name: "headers-checker-sidebar",
						icon: "editor-spellcheck",
						title: "VERIFICAR",
					},
					createElement("div", {
						className: "header-checker",
						children: createElement(Button, {
							isPrimary: true,
							isLarge: true,
							onClick: () => verifyHeaders(),
							children: "Analyze Headers",
						}),
					})
				);
			},
		});
	});
})();
