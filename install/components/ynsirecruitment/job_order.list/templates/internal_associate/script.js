BX.ready(function(){
    if(typeof BX.Main.grid !== 'undefined' ) {
        BX.Main.grid.prototype.associateSelectedtoCandidate  = function() {
            debugger;
            var ID = $('#grid_associate_button_control').attr('data-value'); // or argv(1).firstChildrent();
            var data = { 'ID': this.getRows().getSelectedIds() };
            var values = this.getActionsPanel().getValues();
            data[this.getActionKey()] = 'associate';
            data['CURRENT_TYPE_ID'] = ID;
            data[this.getForAllKey()] = this.getForAllKey() in values ? values[this.getForAllKey()] : 'N';
            this.reloadTable('POST', data);
        };
    }
});