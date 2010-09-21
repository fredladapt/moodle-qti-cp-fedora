
function preventbubble(e){
 if (e && e.stopPropagation) //if stopPropagation method supported
	 e.stopPropagation()
 else
	 event.cancelBubble=true
}

function tree_onclick(e, el){
	if (!e){
		e = window.event
	}
	preventbubble(e); 
	for(var i = 0; i< el.childNodes.length; i++){
		var child;
		child = el.childNodes.item(i);
		if(child.tagName == 'ul' || child.tagName == 'UL' ){
			child.style.display = child.style.display == 'none' ? '' : 'none';
		}
	}
	return false;
	
}

function select_collection(e, el, id, title, id_el_name, title_el_name){
	preventbubble(e);
	
	var id_el, title_el;
	id_el = document.getElementsByName(id_el_name);
	id_el = id_el.length>0 ? id_el[0] : null;
	title_el = document.getElementsByName(title_el_name);
	title_el = title_el.length>0 ? title_el[0] : null;
	if(id_el != null){
		id_el.value = id;
	}
	if(title_el != null){
		title_el.value = title;
	}
	return false;
}