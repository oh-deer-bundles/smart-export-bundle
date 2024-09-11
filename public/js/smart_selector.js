class SmartSelector {
    constructor (options = {}) {
        // DOM elements
        this.container = options.container || null;
        this.chosen_target = null;
        this.available_target = null;
        this.items = [];
        this.current_drag_item = null;
        this.input_fields = null;
        this.buttons = {
            choose_all: null,
            choose_selected: null,
            reset_all: null,
            reset_selected: null
        };
        // counters for buttons status
        this.stats = {
            available: 0,
            available_selected: 0,
            chosen: 0,
            chosen_selected: 0
        };

        if (this.container) {
            // Add drag events on container
            this.initContainer();
            //get available and chosen targets
            this.initTargets();
            //get the collection of draggable items and add their draggable attribute, click event
            this.initItems();
            //get all four buttons and add event click and status disable
            this.initButtons();
            // one input is hidden to post select fields, this updated each change item move
            this.initFields();
            //set the stats to display the right button status on construct
            this.setStats();
        }


    }
    initContainer(){
        const _self = this;
        //dragstart event to initiate mouse dragging
        _self.container.addEventListener('dragstart', function(e)
        {
            console.log('dragstart');
            console.log(e);
            //set the item reference to this element
            _self.current_drag_item = e.target;

            //we don't need the transfer data, but we have to define something
            //otherwise the drop action won't work at all in firefox
            //most browsers support the proper mime-type syntax, eg. "text/plain"
            //but we have to use this incorrect syntax for the benefit of IE10+
            e.dataTransfer.setData('text', '');

        }, false);
        //dragover event to allow the drag by preventing its default
        //ie. the default action of an element is not to allow dragging
        _self.container.addEventListener('dragover', function(e)
        {
            //console.log('dragover');
            //console.log(e);
            if(_self.current_drag_item)
            {
                e.preventDefault();
            }

        }, false);

        //drop event to allow the element to be dropped into valid targets
        _self.container.addEventListener('drop', function(e)
        {
            console.log('drop');
            const target = (e.target.getAttribute('data-draggable') === 'target') ?
                e.target : (e.target.parentNode.getAttribute('data-draggable') === 'target') ? e.target.parentNode : null;

            const previous = (e.target.getAttribute('data-draggable') === 'item') ? e.target : null;

            if(previous) {
                console.log(previous);
            }

            if(!target){
                console.log(e);
            }
            //if this element is a drop target, move the item here
            //then prevent default to allow the action (same as dragover)
            if(target && _self.current_drag_item)
            {

                _self.insertItem(target,_self.current_drag_item, previous);
                e.preventDefault();
            }

        }, false);

        //dragend event to clean-up after drop or abort
        //which fires whether or not the drop target was valid
        _self.container.addEventListener('dragend', function(e)
        {
            console.log('dragend');
            console.log(e);
            _self.current_drag_item = null;
            _self.setStats();

        }, false);
    }
    initTargets(){
        const _self = this;
        const targets = (_self.container) ? _self.container.querySelectorAll('[data-draggable="target"]') : [];
        for(let len = targets.length,
                i=0; i < len; i++) {
            if(targets[i].classList.contains('available')){
                _self.available_target = targets[i];
            }
            if(targets[i].classList.contains('chosen')){
                _self.chosen_target = targets[i];
            }
        }
    }
    initItems(){
        const _self = this;
        _self.items = (_self.container) ? _self.container.querySelectorAll('[data-draggable="item"]') : [];
        for(let len = _self.items.length,
                i = 0; i < len; i ++)
        {
            _self.items[i].setAttribute('draggable', 'true');
            _self.items[i].addEventListener('click', function(e){
                
                ( _self.items[i].classList.contains('selected')) ?
                    _self.items[i].classList.remove('selected') :
                    _self.items[i].classList.add('selected') ;

                _self.setStats();
            }, false);
        }
    }
    initButtons(){
        const _self = this;
        const buttons = (_self.container) ? _self.container.querySelectorAll("button") : [];
        for(let len = buttons.length,
                i=0; i < len; i++) {
            if(buttons[i].classList.contains('choose_all')){
                _self.buttons.choose_all = buttons[i];
                _self.buttons.choose_all.addEventListener('click', function(e){
                    for(let items = _self.available_target.querySelectorAll('[data-draggable="item"]'),
                            len = items.length,
                            i=0; i < len; i++){
                        _self.insertItem(_self.chosen_target, items[i]);
                    }
                    _self.setStats();
                }, false);
            }

            if(buttons[i].classList.contains('choose_selected')){
                _self.buttons.choose_selected = buttons[i];
                _self.buttons.choose_selected.addEventListener('click', function(e){
                    for(let items = _self.available_target.querySelectorAll('[data-draggable="item"]'),
                            len = items.length,
                            i=0; i < len; i++){
                        if(items[i].classList.contains('selected')) {
                            _self.insertItem(_self.chosen_target, items[i]);
                        }
                    }
                    _self.setStats();
                }, false);
            }

            if(buttons[i].classList.contains('reset_all')){
                _self.buttons.reset_all = buttons[i];
                _self.buttons.reset_all.addEventListener('click', function(e){
                    for(let items = _self.chosen_target.querySelectorAll('[data-draggable="item"]'),
                            len = items.length,
                            i=0; i < len; i++){
                        _self.insertItem(_self.available_target, items[i]);
                    }
                    _self.setStats();
                }, false);
            }

            if(buttons[i].classList.contains('reset_selected')){
                _self.buttons.reset_selected = buttons[i];
                _self.buttons.reset_selected.addEventListener('click', function(e){
                    for(let items = _self.chosen_target.querySelectorAll('[data-draggable="item"]'),
                            len = items.length,
                            i=0; i < len; i++){
                        if(items[i].classList.contains('selected')) {
                            _self.insertItem(_self.available_target, items[i]);
                        }
                    }
                    _self.setStats();
                }, false);
            }
            
            _self.container.addEventListener('stats_updated', function(e){
                if(_self.buttons.choose_all){
                    (_self.stats.available > 0) ?
                        _self.buttons.choose_all.classList.remove('disabled') :
                        _self.buttons.choose_all.classList.add('disabled');
                }

                if(_self.buttons.choose_selected) {
                    (_self.stats.available_selected > 0) ?
                        _self.buttons.choose_selected.classList.remove('disabled') :
                        _self.buttons.choose_selected.classList.add('disabled');
                }

                if(_self.buttons.reset_all){
                    (_self.stats.chosen > 0) ?
                        _self.buttons.reset_all.classList.remove('disabled') :
                        _self.buttons.reset_all.classList.add('disabled');
                }

                if(_self.buttons.reset_selected){
                    (_self.stats.chosen_selected > 0) ?
                        _self.buttons.reset_selected.classList.remove('disabled') :
                        _self.buttons.reset_selected.classList.add('disabled');
                }

            }, false);
        }
    }
    initFields(){
        const _self = this;
        if(!_self.input_fields) {
            _self.input_fields = _self.container.querySelectorAll("input.smart_export_fields")[0];
        }
        _self.setValues();
        _self.container.addEventListener('stats_updated', function(e){
            _self.setValues();
        }, false);
    }
    insertItem(target, item, previous){
        console.log('dropItem');
        if (previous) {
            target.insertBefore(item, previous);
        }
        else {
            target.appendChild(item);
        }
        item.classList.remove('selected');
    }
    
    setStats(){
        const _self = this;
        _self.stats = {
            available: 0,
            available_selected: 0,
            chosen: 0,
            chosen_selected: 0
        };
        ['available','chosen'].forEach((target_class)=>{
            const self_key = target_class + '_target';
            if(_self[self_key]){
                for(let items = _self[self_key].querySelectorAll('[data-draggable="item"]'),
                        len = items.length,
                        i = 0; i < len; i ++)
                {
                    _self.stats[target_class]++;
                    if (items[i].classList.contains('selected')) {
                        _self.stats[target_class + '_selected']++;
                    }
                }
            }
        });

        _self.container.dispatchEvent(new Event('stats_updated'));
    }

    getValues(){
        const _self = this;
        let values = [];
        for(let items = _self.chosen_target.querySelectorAll('[data-draggable="item"]'),
                len = items.length,
                i=0; i < len; i++){
            values.push(parseInt(items[i].dataset.value));
        }
        return values;
    }
    
    setValues() {
        console.log('setValues');
        console.log(this.input_fields);
        const _self = this;
        if(_self.input_fields) {
            const field_values = _self.getValues();
            console.log(field_values);
            _self.input_fields.value = field_values.length > 0 ? JSON.stringify(field_values) : '';
        }
    }

}

export default SmartSelector
