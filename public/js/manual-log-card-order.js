(() => {
    const root = document.getElementById('lcOrder'), tbody = document.getElementById('lcOrderRows');
    if (!root) return;
    let groups = JSON.parse(document.getElementById('lcOrderData').textContent), dragged = null, dirty = false, busy = false;
    const message = document.getElementById('lcOrderMessage');
    const headers = {Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content};
    const search = document.getElementById('lcOrderSearch');
    function filterRows() {
        const query = search.value.trim().toLocaleLowerCase();
        let count = 0;
        [...tbody.rows].forEach(row => {
            const group = groups[Number(row.dataset.index)];
            row.hidden = !!query && !group.parts.some(part => [part.ipl, part.pn, part.name].join(' ').toLocaleLowerCase().includes(query));
            if (!row.hidden) count++;
            row.querySelector('.lc-order-handle').draggable = !busy && !query;
        });
        document.getElementById('lcOrderNoResults').hidden = count > 0;
    }
    search.addEventListener('input', filterRows);
    function render() {
        tbody.replaceChildren();
        groups.forEach((group, i) => {
            const row = document.createElement('tr'); row.className = 'lc-order-row'; row.dataset.index = i;
            const handle = document.createElement('td'); handle.textContent = '⠿'; handle.className = 'lc-order-handle'; handle.draggable = true; handle.title = 'Drag row'; row.append(handle);
            const positionCell = document.createElement('td');
            const position = document.createElement('input');
            position.type = 'number'; position.min = '1'; position.max = String(groups.length); position.step = '1';
            position.value = String(i+1); position.className = 'form-control form-control-sm lc-order-position';
            position.ariaLabel = `Position for ${group.parts.map(p=>p.pn).join(', ')}`; position.disabled = busy;
            position.addEventListener('change', () => changePosition(i, position));
            position.addEventListener('keydown', e => { if(e.key === 'Enter'){e.preventDefault(); position.blur();} });
            positionCell.append(position); row.append(positionCell);
            for (const value of ['ipl','pn','name'].map(key => [...new Set(group.parts.map(p=>p[key]))].join('\n'))) {
                const cell=document.createElement('td');cell.className='lc-order-variants';cell.textContent=value;row.append(cell);
            }
            const actions = document.createElement('td'); actions.className = 'lc-order-actions';
            for (const [delta, text] of [[-1,'↑'],[1,'↓']]) {
                const button=document.createElement('button');button.type='button';button.className='btn btn-outline-secondary btn-sm';button.textContent=text;button.ariaLabel=delta<0?'Move up':'Move down';button.disabled=busy||(delta<0?i===0:i===groups.length-1);
                button.onclick=()=>move(i,i+delta);actions.append(button);
            }
            row.append(actions);tbody.append(row);
        });
        filterRows();
    }
    const scrollArea = root.querySelector('.lc-order-scroll');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let destination = null, dragRows = [], dragRects = [], startScroll = 0, ghost = null;
    function move(from,to) {
        if(busy||from===to)return;
        const previous = new Map([...tbody.rows].map(row => [groups[Number(row.dataset.index)].key, row.getBoundingClientRect().top]));
        groups.splice(to,0,groups.splice(from,1)[0]);dirty=true;render();
        [...tbody.rows].forEach((row,i) => {
            const delta = previous.get(groups[i].key)-row.getBoundingClientRect().top;
            if(delta && !reducedMotion.matches)row.animate([{transform:`translateY(${delta}px)`},{transform:'translateY(0)'}],{duration:220,easing:'ease-out'});
        });
        tbody.rows[to].classList.add('lc-order-landed');
    }
    function finishDrag() {
        dragRows.forEach(row=>{row.style.transform='';row.classList.remove('dragging','lc-order-slot');});
        ghost?.remove();ghost=null;dragged=null;destination=null;dragRows=[];dragRects=[];
        root.classList.remove('lc-order-dragging');
    }
    tbody.addEventListener('dragstart',e=>{
        if(busy||search.value.trim()||!e.target.matches('.lc-order-handle')){e.preventDefault();return;}
        const row=e.target.closest('tr');dragged=Number(row.dataset.index);destination=dragged;
        dragRows=[...tbody.rows];dragRects=dragRows.map(r=>r.getBoundingClientRect());startScroll=scrollArea.scrollTop;
        e.dataTransfer.setData('text/plain',String(dragged));e.dataTransfer.effectAllowed='move';
        // Native dragging otherwise shows only the tiny handle, not the chosen part.
        ghost=document.createElement('div');ghost.className='lc-order-ghost';
        ghost.textContent=groups[dragged].parts.map(p=>`(${p.ipl}) ${p.pn}`).join(' / ');
        document.body.append(ghost);e.dataTransfer.setDragImage(ghost,20,20);
        requestAnimationFrame(()=>{if(dragged!==null){row.classList.add('dragging','lc-order-slot');root.classList.add('lc-order-dragging');}});
    });
    scrollArea.addEventListener('dragenter',e=>{if(dragged!==null)e.preventDefault();});
    scrollArea.addEventListener('dragover',e=>{
        if(dragged===null)return;
        e.preventDefault();e.dataTransfer.dropEffect='move';
        const bounds=scrollArea.getBoundingClientRect();
        if(e.clientY>bounds.bottom-45)scrollArea.scrollTop+=16;
        else if(e.clientY<bounds.top+55)scrollArea.scrollTop-=16;
        const y=e.clientY+scrollArea.scrollTop-startScroll;
        let slot=dragRects.findIndex(rect=>y<rect.top+rect.height/2);
        if(slot<0)slot=dragRows.length;
        destination=Math.max(0,Math.min(dragRows.length-1,slot>dragged?slot-1:slot));
        const height=dragRects[dragged].height;
        dragRows.forEach((row,i)=>{
            let offset=0;
            if(i===dragged)offset=destination>dragged ? dragRects[destination].bottom-dragRects[dragged].bottom : dragRects[destination].top-dragRects[dragged].top;
            else if(i>dragged&&i<=destination)offset=-height;
            else if(i<dragged&&i>=destination)offset=height;
            row.style.transform=`translateY(${offset}px)`;
        });
    });
    scrollArea.addEventListener('drop',e=>{
        if(dragged===null)return;
        e.preventDefault();const from=dragged,to=destination;
        move(from,to);finishDrag();
    });
    tbody.addEventListener('dragend',finishDrag);
    function setBusy(value){busy=value;root.querySelectorAll('button,input').forEach(e=>e.disabled=value);render();}
    async function json(response){const data=await response.json();if(!response.ok)throw Error(Object.values(data.errors||{}).flat().join('\n')||data.message||'Request failed.');return data;}
    async function persistOrder() {
        const result = await json(await fetch(root.dataset.save, {method:'PUT', headers:{...headers,'Content-Type':'application/json'},
            body:JSON.stringify({ids:groups.flatMap(g=>g.ids),revision:root.dataset.revision})}));
        root.dataset.revision = result.revision; dirty = false;
    }
    async function changePosition(from, input) {
        if (busy) return;
        const target = Number(input.value);
        if (!input.value || !Number.isInteger(target) || target < 1 || target > groups.length) {
            input.value = String(from+1); message.textContent = `Enter a position from 1 to ${groups.length}.`; return;
        }
        if (target === from+1) return;
        const previous = [...groups], wasDirty = dirty;
        move(from, target-1);
        const movedKey = groups[target-1].key;
        setBusy(true); message.textContent = 'Saving order…';
        try { await persistOrder(); message.textContent = 'Order saved.'; }
        catch (error) { groups = previous; dirty = wasDirty; message.textContent = error.message; }
        finally {
            setBusy(false);
            const index = groups.findIndex(group=>group.key===movedKey);
            const row = tbody.rows[index];
            if (row && !row.hidden) { row.classList.add('lc-order-landed'); row.scrollIntoView({block:'nearest',behavior:reducedMotion.matches?'instant':'smooth'}); }
        }
    }
    document.getElementById('lcOrderSave').onclick=async()=>{
        if(busy)return;
        if(!window.confirmDialog){message.textContent='Confirmation dialog is unavailable.';return;}
        setBusy(true);
        if(!await window.confirmDialog({title:'Save Log Card order',message:'Use this order for new and saved Log Cards of active workorders? QA-locked cards and completed workorders keep their recorded order.',okText:'Save'})){setBusy(false);return;}
        try{await persistOrder();message.textContent='Order saved.';}
        catch(e){message.textContent=e.message;}finally{setBusy(false);}
    };
    window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
    render();
})();

