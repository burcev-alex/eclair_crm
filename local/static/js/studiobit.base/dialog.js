$(function() {
    if(!$.Studiobit)
        $.Studiobit = {};

    $.Studiobit.Dialog = function(action, then, cancel)
    {
        var _this = this;
        this.id = action.id;

        if (action.type == 'confirm')
        {
            action.title = action.title || '';
            action.message = action.message || '';
            action.apply_button = action.apply_button || '';
            action.cancel_button = action.cancel_button || '';
            this.context = action.context || false;

            if(!this.context)
                this.context = window;

            this.dialog = new BX.PopupWindow(
                action.id + '-confirm-dialog',
                this.context,
                {
                    content: '<div class="main-grid-confirm-content">'+action.message+'</div>',
                    titleBar: action.title,
                    autoHide: false,
                    zIndex: 9999,
                    overlay: 0.4,
                    offsetTop: -100,
                    closeIcon : true,
                    closeByEsc : true,
                    events: {
                        onClose: function()
                        {
                            BX.unbind(_this.context, 'keydown', hotKey);
                        }
                    },
                    buttons: [
                        new BX.PopupWindowButton({
                            text: action.apply_button,
                            id: action.id + '-confirm-dialog-apply-button',
                            events: {
                                click: function()
                                {
                                    BX.type.isFunction(then) ? then() : null;
                                    this.popupWindow.close();
                                    this.popupWindow.destroy();
                                    BX.onCustomEvent(_this.context, 'Studiobit::confirmDialogApply', [this]);
                                    BX.unbind(_this.context, 'keydown', _this.hotKey);
                                }
                            }
                        }),
                        new BX.PopupWindowButtonLink({
                            text: action.cancel_button,
                            id: action.id + '-confirm-dialog-cancel-button',
                            events: {
                                click: function()
                                {
                                    BX.type.isFunction(cancel) ? cancel() : null;
                                    this.popupWindow.close();
                                    this.popupWindow.destroy();
                                    BX.onCustomEvent(_this.context, 'Studiobit::confirmDialogCancel', [this]);
                                    BX.unbind(_this.context, 'keydown', _this.hotKey);
                                }
                            }
                        })
                    ]
                }
            );
        }
        else
        {
            BX.type.isFunction(then) ? then() : null;
        }
    }

    $.Studiobit.Dialog.prototype.show = function(){
        if (!this.dialog.isShown())
        {
            this.dialog.show();
            this.applyButton = BX(this.id + '-confirm-dialog-apply-button');
            this.cancelButton = BX(this.id + '-confirm-dialog-cancel-button');

            BX.bind(this.context, 'keydown', this.hotKey);
        }
    }

    $.Studiobit.Dialog.prototype.hotKey = function(event){
        /*if (event.code === 'Enter')
        {
            event.preventDefault();
            event.stopPropagation();
            BX.fireEvent(applyButton, 'click');
        }

        if (event.code === 'Escape')
        {
            event.preventDefault();
            event.stopPropagation();
            BX.fireEvent(cancelButton, 'click');
        }*/
    }
});