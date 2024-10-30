/* 
mi13_like script ver 0.4
*/

async function mi13_like(data=0 ,flag='x') {
	let target = event.target;
	let url = mi13_like_ajax.url+'?action=mi13_like&id='+target.id+'&data='+data+'&flag='+flag;
	if (navigator.cookieEnabled === true) {
		try {
			let response = await fetch(url);
			if( response.ok ) {
				target.parentElement.innerHTML = await response.text();
			}
		} catch(error) {
			target.parentElement.innerText = error;
		}
	} else {
		target.parentElement.children[4].innerText = mi13_like_ajax.message;
	}
}