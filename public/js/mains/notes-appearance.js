(() => {
 let queue = Promise.resolve();
 const initBox = (box) => {
  if (!box || box.dataset.appearanceReady) return;
  const text = box.querySelector('.wo-notes-textarea'), size = box.querySelector('.wo-notes-size');
  if (!text || !size) return;
  box.dataset.appearanceReady = '1';
  const toolsToggle=box.querySelector('.wo-notes-tools-toggle'), toolsPanel=box.querySelector('.wo-notes-tools');
  if(toolsToggle && toolsPanel){
   let touched=false;
   const showTools=open=>{toolsPanel.classList.toggle('is-open',open);toolsPanel.inert=!open;toolsToggle.setAttribute('aria-expanded',String(open));};
   toolsToggle.addEventListener('mousedown',e=>e.preventDefault());
   toolsToggle.addEventListener('click',()=>{
    touched=true;const open=toolsToggle.getAttribute('aria-expanded')!=='true';showTools(open);
    window.UserUiSettings?.set('mains.show','notesFormattingOpen',open);
   });
   window.UserUiSettings?.get('mains.show','notesFormattingOpen',false).then(open=>{if(!touched)showTools(open===true || open==='true');}).catch(()=>{});
  }
  const form = text.form, editor = document.createElement('div');
  editor.className = 'form-control wo-notes-editor'; editor.contentEditable = 'true'; editor.setAttribute('role','textbox'); editor.setAttribute('aria-label','Workorder Notes'); editor.setAttribute('aria-multiline','true');
  text.hidden = true; text.after(editor); form.classList.remove('js-ajax');
  let appearance = {}; try { appearance = JSON.parse(box.dataset.appearance || '{}'); } catch (_) {}
  const sizes = [12,14,16,18,20,24];
  const setStyle=(node,kind,value)=>{
   const key=kind==='color'?'defaultColor':kind==='font-size'?'defaultSize':null;
   if(key){if(value)delete node.dataset[key];else node.dataset[key]='1';}
   node.style.setProperty(kind,value || (kind==='color'?getComputedStyle(editor).color:kind==='font-size'?getComputedStyle(editor).fontSize:'400'),'important');
  };
  const paint = (node,c,s,b) => { setStyle(node,'color',/^#[0-9a-f]{6}$/i.test(c || '')?c:''); setStyle(node,'font-size',sizes.includes(Number(s))?s+'px':''); setStyle(node,'font-weight',b?'700':'400'); };
  const runs = Array.isArray(appearance.runs) && appearance.runs.map(r=>r.text).join('')===text.value ? appearance.runs : [{text:text.value,color:appearance.color,size:appearance.size}];
  runs.forEach(r=>{const span=document.createElement('span');span.textContent=r.text;paint(span,r.color,r.size,r.bold);editor.append(span);});
  let savedRange=null, lastSaved='', timer;
  let typingStyle={};
  const currentRange=()=>{
   if(savedRange && editor.contains(savedRange.commonAncestorContainer))return savedRange.cloneRange();
   const range=document.createRange();range.selectNodeContents(editor);range.collapse(false);return range;
  };
  const restoreRange=range=>{editor.focus();const sel=getSelection();sel.removeAllRanges();sel.addRange(range);savedRange=range.cloneRange();};
  const serialize = () => {
   const result=[];
   const walk=(node,c=null,s=null,b=false)=>{
    if(node.nodeType===3){if(node.data)result.push({text:node.data,color:c,size:s,bold:b});return;}
    if(node.nodeType!==1)return;
    if(node.tagName==='BR'){result.push({text:'\n',color:c,size:s,bold:b});return;}
    const rgb=node.style.color.match(/\d+/g);
    if(node.dataset.defaultColor)c=null;
    else if(rgb)c='#'+rgb.slice(0,3).map(n=>Number(n).toString(16).padStart(2,'0')).join('');
    if(node.dataset.defaultSize)s=null;
    else if(node.style.fontSize)s=sizes.includes(Number.parseFloat(node.style.fontSize))?Number.parseFloat(node.style.fontSize):null;
    if(node.style.fontWeight)b=Number(node.style.fontWeight)>=600 || node.style.fontWeight==='bold';
    for(const child of node.childNodes)walk(child,c,s,b);
   };
   walk(editor);return {notes:result.map(r=>r.text).join(''),runs:result};
  };
  lastSaved=JSON.stringify(serialize());
  const indicator=box.querySelector('.js-notes-save-indicator');
  const save=()=>{
   clearTimeout(timer);const payload=serialize(), snapshot=JSON.stringify(payload);text.value=payload.notes;
   queue=queue.then(async()=>{
    if(snapshot===lastSaved)return;
    const response=await fetch(form.action,{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},body:snapshot,spinner:false});
    if(!response.ok)throw new Error('Save failed');lastSaved=snapshot;
    indicator?.classList.toggle('d-none',JSON.stringify(serialize())===lastSaved);
   }).catch(()=>window.showNotification?.('Could not save notes. Please try again.','error',10000));
  };
  document.addEventListener('selectionchange',()=>{const sel=getSelection();if(sel.rangeCount && editor.contains(sel.anchorNode) && editor.contains(sel.focusNode))savedRange=sel.getRangeAt(0).cloneRange();});
  const format=(kind,value,persist=true)=>{
   const range=currentRange();
   typingStyle[kind]=value;
   if(range.collapsed){restoreRange(range);return;}
   const fragment=range.extractContents();
   fragment.querySelectorAll('*').forEach(el=>{el.style.removeProperty(kind);if(kind==='color')delete el.dataset.defaultColor;if(kind==='font-size')delete el.dataset.defaultSize;});
   const span=document.createElement('span');span.append(fragment);
   setStyle(span,kind,value);
   range.insertNode(span);range.selectNodeContents(span);savedRange=range.cloneRange();
   const sel=getSelection();sel.removeAllRanges();sel.addRange(range);if(persist)save();
  };
  const bold=box.querySelector('.wo-notes-bold');
  bold.addEventListener('mousedown',e=>e.preventDefault());
  bold.addEventListener('click',()=>{
   const range=currentRange();
   if(range.collapsed){const parent=range.startContainer.nodeType===1?range.startContainer:range.startContainer.parentElement;const enabled=(typingStyle['font-weight'] || getComputedStyle(parent).fontWeight)!=='700';format('font-weight',enabled?'700':'400');bold.setAttribute('aria-pressed',String(enabled));bold.classList.toggle('active',enabled);return;}
   const walker=document.createTreeWalker(editor,NodeFilter.SHOW_TEXT); let node,allBold=true,found=false;
   while(node=walker.nextNode())if(node.data && range.intersectsNode(node)){found=true;if(parseInt(getComputedStyle(node.parentElement).fontWeight)<600)allBold=false;}
   const enabled=!(found && allBold); format('font-weight',enabled?'700':'400');bold.setAttribute('aria-pressed',String(enabled));bold.classList.toggle('active',enabled);
  });
  box.querySelectorAll('.wo-notes-swatch').forEach(button=>{
   button.addEventListener('mousedown',e=>e.preventDefault());
   button.addEventListener('click',()=>{format('color',button.dataset.color);});
  });
  size.addEventListener('change',()=>format('font-size',size.value ? size.value+'px' : ''));
  const reset=box.querySelector('.wo-notes-style-reset');
  reset.addEventListener('mousedown',e=>e.preventDefault());
  reset.addEventListener('click',()=>{format('color','',false);format('font-size','',false);format('font-weight','400');bold.setAttribute('aria-pressed','false');bold.classList.remove('active');size.value='';});
  const insertStyledText=value=>{
   const range=currentRange();range.deleteContents();const span=document.createElement('span');span.textContent=value;
   Object.entries(typingStyle).forEach(([kind,v])=>setStyle(span,kind,v));
   range.insertNode(span);range.selectNodeContents(span);range.collapse(false);restoreRange(range);editor.dispatchEvent(new Event('input',{bubbles:true}));
  };
  editor.addEventListener('beforeinput',e=>{if(e.inputType==='insertText' && !e.isComposing && Object.keys(typingStyle).length && e.data!==null){e.preventDefault();insertStyledText(e.data);}});
  editor.addEventListener('input',()=>{indicator?.classList.remove('d-none');clearTimeout(timer);timer=setTimeout(save,800);});
  editor.addEventListener('blur',save);
  editor.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();if(e.ctrlKey||e.metaKey)save();else if(Object.keys(typingStyle).length)insertStyledText('\n');else document.execCommand('insertText',false,'\n');}});
  editor.addEventListener('paste',e=>{e.preventDefault();if(Object.keys(typingStyle).length)insertStyledText(e.clipboardData.getData('text/plain'));else document.execCommand('insertText',false,e.clipboardData.getData('text/plain'));});
 };
 const init = () => initBox(document.querySelector('.main-notes-block'));
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();

