

// ------------------------------------------------------------------------------------------------------------------------------




//Индексный и ассоциативный массивы для блоков скрытия "Остальных товаров"
var wrap_blocks_assoc = new Array();//Производитель+Артикул_+blok указывает на индекс
var wrap_blocks_index = new Array();//Индекс указывает на Производитель+Артикул_+blok
var wrap_states = new Array();//Индекс указывает на флаг Открыт(true)/Закрыт(false)
//Скрываем / Открываем блок. immediately - флаг. Скрыть сразу, т.е. без анимации
function show_hide_block(id, immediately)
{
    var show_hide_button = document.getElementById(M.prefix_show_hide_button+id);
    
	if(show_hide_button == undefined){
		return;
	}
	
	var selector = "."+M.prefix_hide_row_by_id+id;

	if (M.template === 'modern' && M.hidden_row_class)
	{
		if(wrap_states[id] == false)
		{
			wrap_states[id] = true;
			$(selector).removeClass(M.hidden_row_class);
			show_hide_button.innerHTML = M.txt_hide_rows;
		}
		else
		{
			wrap_states[id] = false;
			$(selector).addClass(M.hidden_row_class);
			show_hide_button.innerHTML = M.txt_show_more_rows;
		}
		return;
	}

    //Обращаем состояние блока
    if(wrap_states[id] == false)
    {
        //Открываем
        wrap_states[id] = true;
        if(immediately)
        {
			$(selector).css('display', 'table-row');
        }
        else
        {
            $(selector).show("slow");
        }
        show_hide_button.innerHTML = M.txt_hide_rows;
    }
    else//Скрываем
    {
        wrap_states[id] = false;
        if(immediately)
        {
            $(selector).css('display', 'none');
        }
        else
        {
            $(selector).hide(300);
        }
        show_hide_button.innerHTML = M.txt_show_more_rows;
    }
}




// ------------------------------------------------------------------------------------------------------------------------------

