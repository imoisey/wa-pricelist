$(function(){
    var App = {
        init: function() {
            // Списки и кнопки
            $('select.dropdown').dropdown();
            $('.ui.checkbox').checkbox();

            this.setEvents();
        }, 
        setEvents: function() {
            // Показать категории шаблона
            $(".catview").on('click', function(e){
                e.preventDefault();
                $.fn.waDialog({
                    url: $(this).attr('href'),
                    buttons: '<input class="ui green button" type="submit" value="Сохранить" /> <input class="ui red button cancel" type="button" value="Отмена" />',
                    onLoad: function(){
                        // Обновляем checkbox
                        $('.ui.checkbox').checkbox();
                    },
                    onSubmit: function(d){
                        $.post("?plugin=pricelist&action=update", $(this).serialize(), function(response){
                            if(response.data == 'OK') {
                                d.trigger('close');
                            }
                        });
                        return false;
                    }
                });
                return false;
            });
        }
    }

    App.init();
});