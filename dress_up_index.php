<?php
session_start();
require_once '../../db_connect.php';
require_once 'includes/functions.php';

$userId = dressUpCurrentUserId();
$catalogByLayer = dressUpGetShopCatalogForUser($conn, $userId);
$activeOutfitData = dressUpGetActiveOutfitData($conn, $userId);
$sessionOutfit = applyConflictRules($_SESSION['current_outfit'] ?? []);
$initialOutfit = !empty($activeOutfitData['outfit']) ? $activeOutfitData['outfit'] : $sessionOutfit;
$initialBalance = $userId !== '0' ? coin_get_balance($conn, $userId) : 0;
$itemCount = 0;
foreach ($catalogByLayer as $items) {
    $itemCount += count($items);
}

$defaultReturnUrl = '/galgame/galgame/index.html';
$returnUrl = isset($_GET['return']) ? (string)$_GET['return'] : $defaultReturnUrl;
if ($returnUrl === '' || preg_match('/^(https?:)?\/\//i', $returnUrl)) {
    $returnUrl = $defaultReturnUrl;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Wardrobe - Dress Up Game</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{min-height:100vh;padding:20px;font-family:"Segoe UI",sans-serif;background:linear-gradient(135deg,#f7ead9,#f2d6bf 52%,#f7efe7);color:#543b2c}
        .container{max-width:1440px;margin:0 auto}
        .panel{background:rgba(255,252,246,.95);border-radius:30px;padding:24px;box-shadow:0 24px 56px rgba(122,82,54,.16)}
        .top{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;margin-bottom:22px}
        .title h1{font-size:clamp(2.1rem,3vw,3rem);color:#8f4f34}
        .title p{margin-top:8px;color:#8f6b56}
        .actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
        .wallet{padding:12px 18px;border-radius:20px;background:linear-gradient(135deg,#fff2d8,#f9d89c);color:#7f4e12;min-width:180px}
        .wallet strong{display:block;font-size:1.25rem}
        .btn,.modal-btn,.buy-btn{border:none;border-radius:999px;padding:12px 22px;font-weight:700;cursor:pointer}
        .btn{background:linear-gradient(135deg,#c97d58,#b96545);color:#fff}
        .btn.alt{background:linear-gradient(135deg,#ecd2bd,#e2b89b);color:#7b543e}
        .layout{display:flex;gap:28px;flex-wrap:wrap}
        .stage{flex:1.02;min-width:380px}
        .controls{flex:1.7;min-width:470px}
        .canvas-wrap{padding:18px;border-radius:24px;background:rgba(255,255,255,.72);box-shadow:inset 0 0 0 1px rgba(160,115,78,.08)}
        canvas{display:block;width:100%;max-width:550px;height:auto;margin:0 auto;border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.75),rgba(248,236,226,.95));box-shadow:0 12px 32px rgba(109,76,53,.18)}
        .note{margin-bottom:12px;color:#8a6b58;font-size:.92rem}
        .main-nav,.toolbar,.saved-list{display:flex;gap:12px;flex-wrap:wrap}
        .main-nav{margin-bottom:16px}
        .main-nav button,.sub-nav button{border:none;cursor:pointer;font-weight:700}
        .main-nav button{padding:12px 20px;border-radius:999px;background:#fff4ec;color:#7e604d}
        .main-nav button.active{background:linear-gradient(135deg,#b96f51,#8d4f36);color:#fff}
        .sub-nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:12px;border-radius:18px;background:#fff5ed}
        .sub-nav button{padding:8px 16px;border-radius:999px;background:#fff;color:#7b6454}
        .sub-nav button.active{background:#b26d50;color:#fff}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(138px,1fr));gap:16px;min-height:390px;max-height:470px;overflow-y:auto;padding:16px;border-radius:24px;background:#fff6ef}
        .card{background:#fff;border-radius:20px;padding:12px;text-align:center;box-shadow:0 10px 22px rgba(145,103,73,.1);border:2px solid transparent;cursor:pointer}
        .card.selected{border-color:#a45f42;box-shadow:0 0 0 4px rgba(164,95,66,.14),0 18px 28px rgba(145,103,73,.14)}
        .card.locked{background:linear-gradient(180deg,#fff,#f8f1ea)}
        .thumb{width:100%;height:112px;display:grid;place-items:center;border-radius:16px;background:linear-gradient(180deg,#faf5ef,#f5e9dd);overflow:hidden;margin-bottom:10px}
        .thumb img{width:100%;height:100%;object-fit:contain}
        .name{font-size:.86rem;font-weight:700;color:#6a5343}
        .price{margin-top:6px;font-size:.8rem;font-weight:700;color:#9a6d4d}
        .state{display:inline-block;margin-top:8px;padding:5px 10px;border-radius:999px;font-size:.72rem;font-weight:700;background:#f7ecdf;color:#9b6643}
        .state.unlocked{background:#e9f6ea;color:#2d8a46}
        .state.selected{background:#f6e4db;color:#994e32}
        .buy-btn{margin-top:10px;width:100%;padding:10px 12px;background:linear-gradient(135deg,#db9d52,#c87448);color:#fff}
        .buy-btn:disabled{opacity:.55;cursor:not-allowed}
        .toolbar{justify-content:center;margin-top:22px}
        .saved{margin-top:24px;padding:22px;border-radius:24px;background:#fff6ef}
        .saved h3{color:#8c5337;font-size:1.2rem}
        .saved p{margin-top:6px;margin-bottom:14px;color:#8b705e;font-size:.9rem}
        .saved-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:999px;background:#fff;color:#745846;box-shadow:0 8px 20px rgba(144,104,76,.08);cursor:pointer}
        .saved-item.used{background:#edf9ef;color:#1f1f1f}
        .used{padding:4px 8px;border-radius:999px;background:#32a852;color:#fff;font-size:.7rem;font-weight:700}
        .delete{width:22px;height:22px;display:grid;place-items:center;border-radius:50%;background:#f8d7cf;color:#b14b35;font-size:.8rem;font-weight:800}
        .loading,.empty{display:grid;place-items:center;min-height:180px;color:#9e7e68;font-weight:600}
        .stats{margin-top:14px;text-align:center;color:#886d5d;font-size:.92rem;min-height:22px}
        .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
        .modal.open{display:flex}
        .mask{position:absolute;inset:0;background:rgba(58,36,24,.38);backdrop-filter:blur(5px)}
        .dialog{position:relative;width:min(480px,100%);padding:24px;border-radius:28px;background:linear-gradient(180deg,#fffaf5,#f7ede3);box-shadow:0 30px 60px rgba(80,53,35,.25)}
        .dialog h3{font-size:1.4rem;color:#8b4e34;margin-bottom:10px}
        .dialog p{color:#755c4b;line-height:1.6}
        .icon{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;font-size:1.35rem;background:#fdebd6;color:#b96c34;margin-bottom:16px}
        .modal-input{width:100%;margin-top:16px;padding:14px 16px;border-radius:18px;border:1px solid rgba(184,138,104,.3);background:#fff;font-size:1rem;color:#5d4537;outline:none}
        .modal-actions{margin-top:18px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
        .modal-btn{background:linear-gradient(135deg,#c77b58,#b15d41);color:#fff}
        .modal-btn.secondary{background:linear-gradient(135deg,#ecd2bd,#e2b89b);color:#7b543e}
        @media (max-width:980px){body{padding:14px}.panel{padding:18px}.stage,.controls{min-width:100%}}
    </style>
</head>
<body>
<div class="container">
    <div class="panel">
        <div class="top">
            <div class="title">
                <h1>Dream Wardrobe</h1>
            </div>
            <div class="actions">
                <div class="wallet"><strong id="coinBalance"><?php echo (int)$initialBalance; ?> coins</strong><span>Current balance</span></div>
                <button id="bindBtn" type="button" class="btn alt">Use This Look</button>
                <button type="button" class="btn" onclick="window.location.href='<?php echo htmlspecialchars($returnUrl, ENT_QUOTES); ?>'">Back to Galgame</button>
            </div>
        </div>
        <div class="layout">
            <div class="stage">
                <div class="canvas-wrap"><canvas id="gameCanvas" width="550" height="650"></canvas></div>
            </div>
            <div class="controls">
                <div class="main-nav" id="mainNav"></div>
                <div class="sub-nav" id="subNav"></div>
                <div class="grid" id="imagesGrid"><div class="loading">Loading wardrobe...</div></div>
            </div>
        </div>
        <div class="toolbar">
            <button id="randomBtn" class="btn">Random Outfit</button>
            <button id="saveBtn" class="btn">Save Outfit</button>
            <button id="resetBtn" class="btn alt">Reset All</button>
        </div>
        <div class="saved">
            <h3>My Outfits</h3>
            <div class="saved-list" id="savedList"></div>
        </div>
    </div>
</div>
<div class="modal" id="modalRoot" aria-hidden="true">
    <div class="mask"></div>
    <div class="dialog">
        <div class="icon" id="modalIcon">✦</div>
        <h3 id="modalTitle">Wardrobe</h3>
        <p id="modalMessage"></p>
        <input type="text" id="modalInput" class="modal-input" style="display:none;" maxlength="60">
        <div class="modal-actions">
            <button type="button" class="modal-btn secondary" id="modalCancel">Cancel</button>
            <button type="button" class="modal-btn" id="modalConfirm">OK</button>
        </div>
    </div>
</div>
<script>
const phpCatalog=<?php echo json_encode($catalogByLayer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const initialOutfit=<?php echo json_encode($initialOutfit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const initialActiveOutfit=<?php echo json_encode($activeOutfitData ?: ['id'=>null,'name'=>'','outfit'=>[]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const initialBalance=<?php echo (int)$initialBalance; ?>;
const totalItemsCount=<?php echo (int)$itemCount; ?>;

let coinBalance=initialBalance;
let currentOutfit=initialOutfit||{};
let savedOutfits=[];
let currentMain='body';
let currentSub='body';
let selectedLoadedOutfitId=initialActiveOutfit&&initialActiveOutfit.id?Number(initialActiveOutfit.id):null;
let selectedLoadedOutfitName=initialActiveOutfit&&initialActiveOutfit.name?initialActiveOutfit.name:'';
let selectedLoadedOutfitSignature='';
let modalResolver=null;

const allImages={};
const imageMetaById={};
const canvas=document.getElementById('gameCanvas');
const ctx=canvas.getContext('2d');
const statsDiv=document.getElementById('stats');
const coinBalanceEl=document.getElementById('coinBalance');
const modalRoot=document.getElementById('modalRoot');
const modalTitle=document.getElementById('modalTitle');
const modalMessage=document.getElementById('modalMessage');
const modalInput=document.getElementById('modalInput');
const modalConfirm=document.getElementById('modalConfirm');
const modalCancel=document.getElementById('modalCancel');
const modalIcon=document.getElementById('modalIcon');

const mainNavConfig={
    body:{name:'Body',layers:['body']},
    face:{name:'Face',subLayers:['eye','eyebrows','nose','mouse']},
    hair:{name:'Hair',layers:['hair']},
    clothes:{name:'Clothes',subLayers:['top','pants','dress','suit']},
    shoes:{name:'Shoes',layers:['shoes']},
    sunglasses:{name:'Glasses',layers:['glass']},
    headwear:{name:'Headwear',layers:['head']},
    character:{name:'Character',layers:['character']},
    background:{name:'Background',layers:['background']}
};

const subNames={
    body:'Body',eye:'Eyes',eyebrows:'Eyebrows',nose:'Nose',mouse:'Mouth',
    hair:'Hair',top:'Top',pants:'Pants',dress:'Dress',suit:'Suit',
    shoes:'Shoes',glass:'Glasses',head:'Headwear',character:'Character',background:'Background'
};

const layerOrder=['background','body','shoes','top','pants','dress','suit','eye','eyebrows','nose','mouse','hair','character','glass','head'];

for(const [layer,images] of Object.entries(phpCatalog)){
    allImages[layer]=images.map((img)=>{
        const normalized={
            id:Number(img.id),
            name:img.name,
            layer_code:img.layer_code,
            full_url:img.full_url||('.'+img.file_path),
            thumbnail_url:img.thumbnail_url||img.full_url||('.'+img.file_path),
            price_coins:Number(img.price_coins||0),
            is_free:Number(img.is_free||0),
            is_unlocked:Number(img.is_unlocked||0),
            unlock_source:img.unlock_source||''
        };
        imageMetaById[normalized.id]=normalized;
        return normalized;
    });
}

function applyConflictRules(outfit){
    const result={...outfit};
    if(result.dress){delete result.top;delete result.pants;delete result.suit}
    if(result.suit){delete result.top;delete result.pants;delete result.dress}
    if(result.character){delete result.eye;delete result.eyebrows;delete result.nose;delete result.mouse;delete result.hair}
    return result;
}

function buildOutfitSignature(outfit){
    const normalized=applyConflictRules(outfit||{});
    const sortedKeys=Object.keys(normalized).sort();
    const sortedOutfit={};
    sortedKeys.forEach((key)=>{sortedOutfit[key]=Number(normalized[key])});
    return JSON.stringify(sortedOutfit);
}

if(selectedLoadedOutfitId){selectedLoadedOutfitSignature=buildOutfitSignature(currentOutfit)}

function formatCoins(value){return `${Number(value||0)} coins`}
function updateBalanceUI(){coinBalanceEl.textContent=formatCoins(coinBalance)}
function escapeHtml(value){return String(value||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}
function setStatus(message,isError=false){statsDiv.textContent=message;statsDiv.style.color=isError?'#b14b35':'#5f7d44'}

function updateSummaryUI(){}

function openDialog({title,message,confirmText='OK',cancelText='Cancel',showCancel=false,input=false,defaultValue='',icon='✦'}){
    modalTitle.textContent=title;
    modalMessage.textContent=message;
    modalConfirm.textContent=confirmText;
    modalCancel.textContent=cancelText;
    modalCancel.style.display=showCancel?'inline-flex':'none';
    modalInput.style.display=input?'block':'none';
    modalInput.value=input?defaultValue:'';
    modalIcon.textContent=icon;
    modalRoot.classList.add('open');
    modalRoot.setAttribute('aria-hidden','false');
    return new Promise((resolve)=>{
        modalResolver=resolve;
        setTimeout(()=>{input?modalInput.focus():modalConfirm.focus()},0);
    });
}

function closeDialog(result){
    modalRoot.classList.remove('open');
    modalRoot.setAttribute('aria-hidden','true');
    if(modalResolver){modalResolver(result);modalResolver=null}
}

modalConfirm.addEventListener('click',()=>closeDialog(modalInput.style.display!=='none'?{confirmed:true,value:modalInput.value.trim()}:{confirmed:true}));
modalCancel.addEventListener('click',()=>closeDialog({confirmed:false,value:''}));
modalRoot.addEventListener('click',(event)=>{if(event.target.classList.contains('mask'))closeDialog({confirmed:false,value:''})});
modalInput.addEventListener('keydown',(event)=>{if(event.key==='Enter'){event.preventDefault();closeDialog({confirmed:true,value:modalInput.value.trim()})}});

async function showNotice(title,message,isError=false){
    await openDialog({title,message,confirmText:'OK',icon:isError?'!':'✦'});
}

async function askForName(title,message,defaultValue=''){
    const result=await openDialog({title,message,confirmText:'Save',cancelText:'Cancel',showCancel:true,input:true,defaultValue,icon:'✎'});
    return !result.confirmed||!result.value?null:result.value;
}

async function askForConfirmation(title,message,confirmText='Confirm'){
    const result=await openDialog({title,message,confirmText,cancelText:'Cancel',showCancel:true,icon:'✦'});
    return !!result.confirmed;
}

async function autoImport(){
    try{
        const res=await fetch('api/auto_import.php');
        const data=await res.json();
        if(data.success&&data.imported>0){
            setStatus(`Auto imported ${data.imported} new wardrobe items`);
            setTimeout(()=>location.reload(),1000);
        }
    }catch(error){console.error(error)}
}

async function postJson(url,payload){
    const res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    return res.json();
}

function isItemUnlocked(imageId){return Number(imageMetaById[imageId]?.is_unlocked||0)===1}
function getCurrentLayer(){const config=mainNavConfig[currentMain];if(config.subLayers)return currentSub;if(config.layers)return config.layers[0];return 'body'}

async function syncCurrentOutfit(showFeedback=false){
    try{
        const res=await fetch('api/set_current_outfit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({items:applyConflictRules(currentOutfit)})});
        const data=await res.json();
        if(showFeedback){
            if(data.success){await showNotice('Look Linked','Your current look is now synced to the galgame session.')}
            else{await showNotice('Link Failed',data.error||'Failed to sync the current look.',true)}
        }
    }catch(error){
        console.error(error);
        if(showFeedback)await showNotice('Link Failed','Failed to sync the current look.',true);
    }
}

async function uploadAvatarForOutfit(outfitId){
    return postJson('api/upload_outfit_avatar.php',{outfit_id:outfitId,image_data:canvas.toDataURL('image/png')});
}

function pickRandomUnlocked(layer){
    const candidates=(allImages[layer]||[]).filter((item)=>isItemUnlocked(item.id));
    return candidates.length?candidates[Math.floor(Math.random()*candidates.length)]:null;
}

async function randomOutfit(){
    const outfit={};
    const body=pickRandomUnlocked('body');
    if(body)outfit.body=body.id;
    const background=pickRandomUnlocked('background');
    if(background&&Math.random()>.3)outfit.background=background.id;
    const shoes=pickRandomUnlocked('shoes');
    if(shoes)outfit.shoes=shoes.id;
    const glass=pickRandomUnlocked('glass');
    if(glass&&Math.random()>.45)outfit.glass=glass.id;
    const head=pickRandomUnlocked('head');
    if(head&&Math.random()>.45)outfit.head=head.id;
    const character=pickRandomUnlocked('character');
    if(character&&Math.random()>.7){
        outfit.character=character.id;
    }else{
        ['eye','eyebrows','nose','mouse','hair'].forEach((layer)=>{
            const item=pickRandomUnlocked(layer);
            if(item)outfit[layer]=item.id;
        });
    }
    const clothingMode=['two_piece','dress','suit'][Math.floor(Math.random()*3)];
    if(clothingMode==='two_piece'){
        const top=pickRandomUnlocked('top');
        const pants=pickRandomUnlocked('pants');
        if(top)outfit.top=top.id;
        if(pants)outfit.pants=pants.id;
    }else if(clothingMode==='dress'){
        const dress=pickRandomUnlocked('dress');
        if(dress)outfit.dress=dress.id;
    }else{
        const suit=pickRandomUnlocked('suit');
        if(suit)outfit.suit=suit.id;
    }
    currentOutfit=applyConflictRules(outfit);
    selectedLoadedOutfitId=null;
    selectedLoadedOutfitName='';
    selectedLoadedOutfitSignature='';
    await renderCanvas();
    renderImagesGrid();
    updateSummaryUI();
    await syncCurrentOutfit();
    setStatus('A new random outfit has been prepared');
}

async function saveOutfit(){
    const normalizedOutfit=applyConflictRules(currentOutfit);
    if(Object.keys(normalizedOutfit).length===0){
        await showNotice('Nothing To Save','Choose at least one unlocked item before saving this outfit.',true);
        return;
    }
    const name=await askForName('Save Outfit','Give this outfit a name so you can find it again later.');
    if(!name)return;
    const data=await postJson('api/save_outfit.php',{name,items:normalizedOutfit});
    if(data.success){
        await syncCurrentOutfit();
        setStatus(`Saved outfit "${name}"`);
        await showNotice('Outfit Saved',`"${name}" has been saved to My Outfits.`);
        loadSavedOutfits();
        return;
    }
    await showNotice('Save Failed',data.error||'The outfit could not be saved.',true);
}

async function useThisLookInMingGame(){
    const normalizedOutfit=applyConflictRules(currentOutfit);
    const currentSignature=buildOutfitSignature(normalizedOutfit);
    if(Object.keys(normalizedOutfit).length===0){
        await showNotice('Look Needed','Please build a look before trying to use it.',true);
        return;
    }

    try{
        await syncCurrentOutfit();

        if(selectedLoadedOutfitId&&selectedLoadedOutfitSignature===currentSignature){
            const activateData=await postJson('api/activate_outfit.php',{outfit_id:selectedLoadedOutfitId});
            if(activateData.success){
                await uploadAvatarForOutfit(selectedLoadedOutfitId);
                initialActiveOutfit.id=selectedLoadedOutfitId;
                initialActiveOutfit.name=selectedLoadedOutfitName||'Unnamed look';
                initialActiveOutfit.outfit={...normalizedOutfit};
                setStatus(`${selectedLoadedOutfitName||'This look'} is now active`);
                updateSummaryUI();
                await showNotice('Look Applied',`${selectedLoadedOutfitName||'This look'} is now the active outfit.`);
                loadSavedOutfits();
            }else{
                await showNotice('Apply Failed',activateData.error||'Failed to apply this look.',true);
            }
            return;
        }

        const matchData=await postJson('api/find_matching_outfit.php',{items:normalizedOutfit});
        if(!matchData.success){
            await showNotice('Check Failed',matchData.error||'Failed to check your existing outfits.',true);
            return;
        }

        if(matchData.match){
            if(Number(matchData.match.is_used)===1){
                selectedLoadedOutfitId=Number(matchData.match.id);
                selectedLoadedOutfitName=matchData.match.name||'';
                selectedLoadedOutfitSignature=currentSignature;
                initialActiveOutfit.id=selectedLoadedOutfitId;
                initialActiveOutfit.name=selectedLoadedOutfitName;
                initialActiveOutfit.outfit={...normalizedOutfit};
                updateSummaryUI();
                await showNotice('Already Active',`${matchData.match.name} is already the active look.`);
                return;
            }

            const activateData=await postJson('api/activate_outfit.php',{outfit_id:matchData.match.id});
            if(activateData.success){
                await uploadAvatarForOutfit(Number(matchData.match.id));
                selectedLoadedOutfitId=Number(matchData.match.id);
                selectedLoadedOutfitName=matchData.match.name||'';
                selectedLoadedOutfitSignature=currentSignature;
                initialActiveOutfit.id=selectedLoadedOutfitId;
                initialActiveOutfit.name=selectedLoadedOutfitName;
                initialActiveOutfit.outfit={...normalizedOutfit};
                updateSummaryUI();
                setStatus(`${matchData.match.name} is now active`);
                await showNotice('Look Applied',`${matchData.match.name} is now the active look.`);
                loadSavedOutfits();
            }else{
                await showNotice('Apply Failed',activateData.error||'Failed to apply this look.',true);
            }
            return;
        }

        const name=await askForName('Name This Look','This is a brand new look. Give it a name before we save and apply it.');
        if(!name)return;
        const data=await postJson('api/save_outfit.php',{name,items:normalizedOutfit,is_used:true});
        if(data.success){
            if(data.outfit_id){
                selectedLoadedOutfitId=Number(data.outfit_id);
                selectedLoadedOutfitName=name;
                selectedLoadedOutfitSignature=currentSignature;
                await uploadAvatarForOutfit(Number(data.outfit_id));
            }
            initialActiveOutfit.id=selectedLoadedOutfitId;
            initialActiveOutfit.name=name;
            initialActiveOutfit.outfit={...normalizedOutfit};
            updateSummaryUI();
            setStatus('Current look is now active');
            await showNotice('Look Applied',`"${name}" has been saved and is now the active look.`);
            loadSavedOutfits();
        }else{
            await showNotice('Apply Failed',data.error||'Failed to apply this look.',true);
        }
    }catch(error){
        console.error(error);
        await showNotice('Apply Failed','Failed to apply this look.',true);
    }
}



function updateOutfit(layer,imageId){
    if(imageId===null){
        delete currentOutfit[layer];
    }else{
        currentOutfit[layer]=imageId;
        if(layer==='dress'){delete currentOutfit.top;delete currentOutfit.pants;delete currentOutfit.suit}
        if(layer==='suit'){delete currentOutfit.top;delete currentOutfit.pants;delete currentOutfit.dress}
        if(layer==='character'){delete currentOutfit.eye;delete currentOutfit.eyebrows;delete currentOutfit.nose;delete currentOutfit.mouse;delete currentOutfit.hair}
        const faceParts=['eye','eyebrows','nose','mouse','hair'];
        if(faceParts.includes(layer))delete currentOutfit.character;
        if(layer==='top'||layer==='pants'){delete currentOutfit.dress;delete currentOutfit.suit}
    }

    currentOutfit=applyConflictRules(currentOutfit);
    if(buildOutfitSignature(currentOutfit)!==selectedLoadedOutfitSignature){
        selectedLoadedOutfitId=null;
        selectedLoadedOutfitName='';
    }
    renderCanvas();
    renderImagesGrid();
    updateSummaryUI();
    syncCurrentOutfit();
}

async function purchaseItem(imageId){
    const meta=imageMetaById[imageId];
    if(!meta)return;
    if(meta.price_coins>coinBalance){
        await showNotice('Not Enough Coins',`You need ${formatCoins(meta.price_coins)} to unlock ${meta.name}, but you only have ${formatCoins(coinBalance)}.`,true);
        return;
    }

    const confirmed=await askForConfirmation('Purchase Item',`Unlock ${meta.name} for ${formatCoins(meta.price_coins)}?`,'Buy Now');
    if(!confirmed)return;

    const data=await postJson('api/purchase_item.php',{image_id:imageId});
    if(!data.success){
        await showNotice('Purchase Failed',data.error||'The item could not be purchased.',true);
        return;
    }

    imageMetaById[imageId].is_unlocked=1;
    imageMetaById[imageId].unlock_source='purchase';
    coinBalance=Number(data.balance||coinBalance);
    updateBalanceUI();
    renderImagesGrid();
    setStatus(`${meta.name} unlocked successfully`);
    await showNotice('Purchase Successful',`${meta.name} is now unlocked. ${data.price_coins>0?`${data.price_coins} coins were spent.`:'This item was free.'}`);
}

async function renderCanvas(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    const outfitToRender=applyConflictRules(currentOutfit);
    for(const layer of layerOrder){
        const imageId=outfitToRender[layer];
        if(!imageId)continue;
        const imgData=allImages[layer]?.find((img)=>img.id===Number(imageId));
        if(!imgData)continue;
        const img=new Image();
        await new Promise((resolve)=>{
            img.onload=()=>{
                const scale=Math.min(canvas.width/img.width,canvas.height/img.height);
                const width=img.width*scale;
                const height=img.height*scale;
                ctx.drawImage(img,(canvas.width-width)/2,(canvas.height-height)/2,width,height);
                resolve();
            };
            img.onerror=()=>resolve();
            img.src=imgData.full_url;
        });
    }
}

function renderMainNav(){
    const container=document.getElementById('mainNav');
    container.innerHTML=Object.entries(mainNavConfig).map(([key,config])=>`<button class="${currentMain===key?'active':''}" data-main="${key}">${config.name}</button>`).join('');
    document.querySelectorAll('#mainNav button').forEach((btn)=>{
        btn.addEventListener('click',()=>{
            currentMain=btn.dataset.main;
            if(currentMain==='face')currentSub='eye';
            else if(currentMain==='clothes')currentSub='top';
            else{
                const config=mainNavConfig[currentMain];
                if(config.layers?.length)currentSub=config.layers[0];
            }
            renderMainNav();
            renderSubNav();
            renderImagesGrid();
        });
    });
}

function renderSubNav(){
    const container=document.getElementById('subNav');
    const config=mainNavConfig[currentMain];
    const subLayers=config.subLayers||config.layers||[];
    if(subLayers.length<=1){
        container.style.display='none';
        return;
    }
    container.style.display='flex';
    container.innerHTML=subLayers.map((layer)=>`<button class="${currentSub===layer?'active':''}" data-layer="${layer}">${subNames[layer]||layer}</button>`).join('');
    document.querySelectorAll('#subNav button').forEach((btn)=>{
        btn.addEventListener('click',()=>{
            currentSub=btn.dataset.layer;
            renderSubNav();
            renderImagesGrid();
        });
    });
}

function renderImagesGrid(){
    const container=document.getElementById('imagesGrid');
    const currentLayer=getCurrentLayer();
    const images=allImages[currentLayer]||[];
    if(images.length===0){
        container.innerHTML='<div class="empty">No wardrobe items in this category.</div>';
        return;
    }

    container.innerHTML=images.map((img)=>{
        const selected=Number(currentOutfit[currentLayer])===img.id;
        const unlocked=isItemUnlocked(img.id);
        const canAfford=coinBalance>=Number(img.price_coins||0);
        const priceLabel=Number(img.price_coins||0)===0?'Free':formatCoins(img.price_coins);
        const stateClass=selected?'selected':(unlocked?'unlocked':'');
        const stateText=selected?'Wearing now':(unlocked?'Unlocked':'Locked');
        return `<div class="card ${selected?'selected':''} ${unlocked?'':'locked'}" data-layer="${currentLayer}" data-id="${img.id}">
            <div class="thumb"><img src="${img.thumbnail_url}" alt="${escapeHtml(img.name)}"></div>
            <div class="name">${escapeHtml(img.name)}</div>
            <div class="price">${priceLabel}</div>
            <div class="state ${stateClass}">${stateText}</div>
            ${unlocked?'':`<button type="button" class="buy-btn" data-buy-id="${img.id}" ${canAfford?'':'disabled'}>${canAfford?'Buy Item':'Need More Coins'}</button>`}
        </div>`;
    }).join('');

    document.querySelectorAll('.card').forEach((card)=>{
        card.addEventListener('click',async(event)=>{
            if(event.target.classList.contains('buy-btn'))return;
            const layer=card.dataset.layer;
            const imageId=Number(card.dataset.id);
            const meta=imageMetaById[imageId];
            if(!meta)return;
            if(!isItemUnlocked(imageId)){
                if(meta.price_coins>coinBalance){
                    await showNotice('Not Enough Coins',`You need ${formatCoins(meta.price_coins)} to unlock ${meta.name}, but you only have ${formatCoins(coinBalance)}.`,true);
                }else{
                    await purchaseItem(imageId);
                }
                return;
            }
            if(Number(currentOutfit[layer])===imageId)updateOutfit(layer,null);
            else updateOutfit(layer,imageId);
        });
    });

    document.querySelectorAll('.buy-btn').forEach((button)=>{
        button.addEventListener('click',async(event)=>{
            event.stopPropagation();
            await purchaseItem(Number(button.dataset.buyId));
        });
    });
}

function reset(){
    currentOutfit={};
    selectedLoadedOutfitId=null;
    selectedLoadedOutfitName='';
    selectedLoadedOutfitSignature='';
    renderCanvas();
    renderImagesGrid();
    updateSummaryUI();
    syncCurrentOutfit();
    setStatus('Current look reset');
}

async function init(){
    await autoImport();
    renderMainNav();
    renderSubNav();
    renderImagesGrid();
    await renderCanvas();
    updateBalanceUI();
    updateSummaryUI();
    loadSavedOutfits();
    document.getElementById('randomBtn').onclick=randomOutfit;
    document.getElementById('saveBtn').onclick=saveOutfit;
    document.getElementById('resetBtn').onclick=reset;
    document.getElementById('bindBtn').onclick=useThisLookInMingGame;
    setStatus(`Loaded ${totalItemsCount} wardrobe items`);
}

init();
</script>
</body>
</html>
