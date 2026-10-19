const {spawn} = require('node:child_process');
const path = require('node:path');
const {pathToFileURL} = require('node:url');
const assert = require('node:assert/strict');
(async () => {
 const executable = process.argv[2] || process.env.YOGA_CHROMIUM;
 if (!executable || !process.argv[3]) throw new Error('Usage: node report-menu-browser.cjs <chromium executable> <generated fixture.html>');
 const child = spawn(executable, ['--headless','--no-sandbox','--disable-gpu','--disable-background-networking','--remote-debugging-port=0','--allow-file-access-from-files']);
 let ws;
 try {
  const endpoint = await new Promise((resolve,reject) => {
   const timeout=setTimeout(()=>reject(new Error('Browser startup timeout')),15000);
   child.stderr.on('data', b=>{const m=b.toString().match(/DevTools listening on (ws:\/\/\S+)/); if(m){clearTimeout(timeout);resolve(m[1]);}});
   child.on('error',reject);
  });
  ws = new WebSocket(endpoint); await new Promise(r=>ws.addEventListener('open',r,{once:true}));
  let next=0; const pending=new Map();
  ws.addEventListener('message',e=>{const m=JSON.parse(e.data);if(pending.has(m.id)){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(new Error(m.error.message)):p.resolve(m.result);}});
  const call=(method,params={},sessionId)=>new Promise((resolve,reject)=>{const id=++next;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params,sessionId}));});
  const {targetId}=await call('Target.createTarget',{url:'about:blank'});
  const {sessionId}=await call('Target.attachToTarget',{targetId,flatten:true});
  const c=(method,params)=>call(method,params,sessionId);
  const evaluate=async expression=>{const r=await c('Runtime.evaluate',{expression,returnByValue:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
  await c('Page.enable');
  const loadAt = async width => {
   await c('Emulation.setDeviceMetricsOverride',{width,height:812,deviceScaleFactor:1,mobile:false});
   await evaluate('window.yogaMenuFixtureReady=false');
   await c('Page.navigate',{url:pathToFileURL(path.resolve(process.argv[3])).href});
   await c('Page.bringToFront');
   for(let i=0;i<50;i++){if(await evaluate('window.yogaMenuFixtureReady === true'))break;await new Promise(r=>setTimeout(r,100));}
   assert.equal(await evaluate('window.yogaMenuFixtureReady'),true,'all production handlers initialized');
   assert.equal(await evaluate('innerWidth'),width,'fixture initializes at the requested width');
  };
  const key=async (name,code,modifiers=0)=>{await c('Input.dispatchKeyEvent',{type:'keyDown',key:name,code:name===' '?'Space':name,windowsVirtualKeyCode:code,modifiers,text:name==='Enter'?'\r':name===' '?' ':undefined});await c('Input.dispatchKeyEvent',{type:'keyUp',key:name,code:name===' '?'Space':name,windowsVirtualKeyCode:code,modifiers});};
  const tabTo = async selector => {
   for(let i=0;i<30;i++) { if(await evaluate(`document.activeElement.matches(${JSON.stringify(selector)})`)) return; await key('Tab',9); }
   throw new Error('Tab cannot reach ' + selector);
  };
  const click = async selector => {
   const p=await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};})()`);
   await c('Input.dispatchMouseEvent',{type:'mousePressed',...p,button:'left',clickCount:1});
   await c('Input.dispatchMouseEvent',{type:'mouseReleased',...p,button:'left',clickCount:1});
  };
  const closed = async () => {
   assert.deepEqual(await evaluate(`({panels:document.querySelectorAll('.modal-mobile-menu.active,.modal-mobile-menu-lk.active').length,overlay:document.querySelector('.overlay').classList.contains('active'),burger:document.querySelector('.burger').classList.contains('active'),header:document.querySelector('.header').classList.contains('active'),aria:document.querySelector('.burger').getAttribute('aria-expanded'),focus:document.activeElement.classList.contains('burger')})`), {panels:0,overlay:false,burger:false,header:false,aria:'false',focus:true});
  };
  for(const initialWidth of [1300,375]) {
   await loadAt(initialWidth);
   // Resize without reload, including a second desktop-to-mobile transition.
   for(const width of [375,1024,1300,375]) {
   await c('Emulation.setDeviceMetricsOverride',{width,height:812,deviceScaleFactor:1,mobile:false});
   if(width===1300) continue;
   await evaluate('document.body.className="body body_lk";document.querySelector(".burger").focus()');
   await key('Enter',13); await key('Tab',9,8);
   assert.equal(await evaluate('document.activeElement.classList.contains("sidebar-exit")'),true);
   await key('Tab',9);
   assert.equal(await evaluate('document.activeElement.classList.contains("modal-close")'),true);
   await key('Escape',27); await closed();
   for(const target of [1,7,3,5,8,6]) {
    await key('Enter',13);
    await tabTo(`button.sidebar-menu__item[data-target="${target}"]`);
    await key('Enter',13);
    assert.equal(await evaluate(`document.querySelector('.lk-slide.active').dataset.target`),String(target));
    await closed();
    await click('.burger'); await click(`button.sidebar-menu__item[data-target="${target}"]`); await closed();
   }
   await evaluate('document.body.className="body body_main";document.querySelector(".burger").setAttribute("aria-controls","yoga-mobile-menu")');
   await key('Enter',13); await tabTo('.mobile-menu-library-toggle'); await key('Enter',13);
   assert.equal(await evaluate('document.activeElement.classList.contains("mobile-menu-back") && document.querySelector(".mobile-menu-library-toggle").getAttribute("aria-expanded")==="true"'),true);
   await key('Enter',13);
   assert.equal(await evaluate('document.activeElement.classList.contains("mobile-menu-library-toggle") && document.querySelector(".mobile-menu-library-toggle").getAttribute("aria-expanded")==="false"'),true);
   await key('Escape',27); await closed();
   }
  }
  console.log('PASS: initial 1300/375px; resize without reload through 375/1024/1300/375px; mouse and Tab/Enter for all 6 LK sections; panels/overlay/classes/ARIA/focus reset; public library/back/Escape.');
 } finally {if(ws)ws.close();child.kill();}
})().catch(e=>{console.error(e);process.exitCode=1;});
