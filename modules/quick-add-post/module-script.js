(function(wp) {

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var Button = wp.components.Button;

    var MyCustomButton = function() {
        return (
            wp.element.createElement(
                PluginDocumentSettingPanel,
                {
                    name: 'my-custom-button-panel',
                    title: 'My Custom Button',
                    className: 'my-custom-button-panel',
                },
                wp.element.createElement(
                    Button,
                    {
                        isSecondary: true,
                        onClick: function() {
                            alert('Button clicked!');
                        }
                    },
                    'Click Me'
                )
            )
        );
    };

    registerPlugin('my-custom-button', {
        render: MyCustomButton
    });
})(window.wp);
