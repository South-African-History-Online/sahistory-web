((u,h)=>{u.behaviors.sahoFeaturedGrid={attach:(t,e)=>{h("saho-featured-grid",".saho-featured-grid",t).forEach(i=>{new n(i)})}};function n(t){this.grid=t,this.categoryButtons=this.grid.querySelectorAll(".saho-category-item"),this.contentSections=this.grid.querySelectorAll(".featured-content-section"),this.sortSelects=this.grid.querySelectorAll('select[id*="sort"]'),this.init()}n.prototype.init=function(){this.bindEvents(),this.initializeSorting(),this.loadDynamicContent()},n.prototype.bindEvents=function(){const t=this;this.categoryButtons.forEach(e=>{e.addEventListener("click",function(i){i.preventDefault(),t.switchCategory(this)}),e.addEventListener("keydown",function(i){(i.key==="Enter"||i.key===" ")&&(i.preventDefault(),t.switchCategory(this))})}),this.sortSelects.forEach(e=>{e.addEventListener("change",function(){t.sortContent(this)})})},n.prototype.switchCategory=function(t){const e=t.getAttribute("data-target"),i=this.grid.querySelector(`#${e}`);i&&(this.categoryButtons.forEach(r=>{r.classList.remove("active"),r.setAttribute("aria-selected","false")}),t.classList.add("active"),t.setAttribute("aria-selected","true"),this.contentSections.forEach(r=>{r.classList.remove("active"),r.style.display="none"}),i.classList.add("active"),i.style.display="block",e!=="all-featured"&&this.loadCategoryContent(e),this.announceChange(t.textContent.trim()))},n.prototype.sortContent=function(t){const e=t.value,r=this.grid.querySelector(".featured-content-section.active").querySelector(".saho-landing-grid");if(!r)return;const c=Array.from(r.children);let s;switch(e){case"title":s=c.sort((a,o)=>{const l=a.getAttribute("data-title")||"",d=o.getAttribute("data-title")||"";return l.localeCompare(d)});break;case"type":s=c.sort((a,o)=>{const l=a.getAttribute("data-node-type")||"",d=o.getAttribute("data-node-type")||"";return l.localeCompare(d)});break;default:s=c.sort((a,o)=>{const l=Number.parseInt(a.getAttribute("data-updated"),10)||0;return(Number.parseInt(o.getAttribute("data-updated"),10)||0)-l});break}s.forEach(a=>{r.appendChild(a)}),this.announceChange(`Content sorted by ${t.options[t.selectedIndex].text}`)},n.prototype.initializeSorting=function(){this.sortSelects.forEach(t=>{if(t.value==="recent"){const e=new Event("change");t.dispatchEvent(e)}})},n.prototype.loadCategoryContent=function(t){const i=this.grid.querySelector(`#${t}`).querySelector(`#${t}-content`);!i||i.hasAttribute("data-loaded")||(i.innerHTML=`
      <div class="col-12 text-center py-5">
        <div class="spinner-border saho-text-primary" role="status" aria-live="polite">
          <span class="visually-hidden">Loading ${t} content...</span>
        </div>
      </div>
    `,setTimeout(()=>{this.renderCategoryContent(t,i)},1e3))},n.prototype.renderCategoryContent=function(t,e){const i=this.grid.querySelector("#all-featured-grid");if(!i){e.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Unable to load content for this category.
          </div>
        </div>
      `,e.setAttribute("data-loaded","true");return}const r=Array.from(i.children);let c=[],s=null;switch(t){case"staff-picks":c=r.filter(a=>a.getAttribute("data-staff-pick")==="1");break;case"most-read":s="/search?sort=views";break;case"africa-section":s="/africa";break;case"politics-society":s="/politics-society";break;case"timelines":s="/timelines";break;default:c=r}if(s){const a=t.replace(/-/g," ").replace(/\b\w/g,o=>o.toUpperCase());e.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="saho-category-redirect p-4 rounded shadow-sm bg-white">
            <i class="fas fa-external-link-alt fa-2x mb-3 saho-text-primary"></i>
            <h4>Explore ${a}</h4>
            <p class="text-muted mb-3">Visit our dedicated ${a} section for more content.</p>
            <a href="${s}" class="btn saho-bg-primary text-white px-4 py-2">
              <i class="fas fa-arrow-right me-2"></i>Go to ${a}
            </a>
          </div>
        </div>
      `,e.setAttribute("data-loaded","true");return}if(c.length>0)e.innerHTML="",c.forEach(a=>{const o=a.cloneNode(!0);e.appendChild(o)});else{const a=t.replace(/-/g," ").replace(/\b\w/g,o=>o.toUpperCase());e.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No ${a} items found in the current featured content.
          </div>
        </div>
      `}e.setAttribute("data-loaded","true")},n.prototype.announceChange=t=>{const e=document.createElement("div");e.setAttribute("aria-live","polite"),e.setAttribute("aria-atomic","true"),e.className="visually-hidden",e.textContent=t,document.body.appendChild(e),setTimeout(()=>{document.body.removeChild(e)},1e3)},window.SahoFeaturedGrid=n})(Drupal,once);
