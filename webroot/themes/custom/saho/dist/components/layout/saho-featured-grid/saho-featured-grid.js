((d,u)=>{d.behaviors.sahoFeaturedGrid={attach:(e,t)=>{u("saho-featured-grid",".saho-featured-grid",e).forEach(i=>{new n(i)})}};function n(e){this.grid=e,this.categoryButtons=this.grid.querySelectorAll(".saho-category-item"),this.contentSections=this.grid.querySelectorAll(".featured-content-section"),this.sortSelects=this.grid.querySelectorAll('select[id*="sort"]'),this.init()}n.prototype.init=function(){this.bindEvents(),this.initializeSorting(),this.loadDynamicContent()},n.prototype.bindEvents=function(){const e=this;this.categoryButtons.forEach(t=>{t.addEventListener("click",function(i){i.preventDefault(),e.switchCategory(this)}),t.addEventListener("keydown",function(i){(i.key==="Enter"||i.key===" ")&&(i.preventDefault(),e.switchCategory(this))})}),this.sortSelects.forEach(t=>{t.addEventListener("change",function(){e.sortContent(this)})})},n.prototype.switchCategory=function(e){const t=e.getAttribute("data-target"),i=this.grid.querySelector(`#${t}`);i&&(this.categoryButtons.forEach(s=>{s.classList.remove("active"),s.setAttribute("aria-selected","false")}),e.classList.add("active"),e.setAttribute("aria-selected","true"),this.contentSections.forEach(s=>{s.classList.remove("active"),s.style.display="none"}),i.classList.add("active"),i.style.display="block",t!=="all-featured"&&this.loadCategoryContent(t),this.announceChange(e.textContent.trim()))},n.prototype.sortContent=function(e){const t=e.value,i=this.grid.querySelector(".featured-content-section.active").querySelector(".saho-landing-grid");if(!i)return;const s=Array.from(i.children);let o;switch(t){case"title":o=s.sort((r,a)=>{const c=r.getAttribute("data-title")||"",l=a.getAttribute("data-title")||"";return c.localeCompare(l)});break;case"type":o=s.sort((r,a)=>{const c=r.getAttribute("data-node-type")||"",l=a.getAttribute("data-node-type")||"";return c.localeCompare(l)});break;default:o=s.sort((r,a)=>{const c=Number.parseInt(r.getAttribute("data-updated"),10)||0;return(Number.parseInt(a.getAttribute("data-updated"),10)||0)-c});break}o.forEach(r=>{i.appendChild(r)}),this.announceChange(`Content sorted by ${e.options[e.selectedIndex].text}`)},n.prototype.initializeSorting=function(){this.sortSelects.forEach(e=>{if(e.value==="recent"){const t=new Event("change");e.dispatchEvent(t)}})},n.prototype.loadCategoryContent=function(e){const t=this.grid.querySelector(`#${e}`).querySelector(`#${e}-content`);!t||t.hasAttribute("data-loaded")||(t.innerHTML=`
      <div class="col-12 text-center py-5">
        <div class="spinner-border saho-text-primary" role="status" aria-live="polite">
          <span class="visually-hidden">Loading ${e} content...</span>
        </div>
      </div>
    `,setTimeout(()=>{this.renderCategoryContent(e,t)},1e3))},n.prototype.renderCategoryContent=function(e,t){const i=this.grid.querySelector("#all-featured-grid");if(!i){t.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Unable to load content for this category.
          </div>
        </div>
      `,t.setAttribute("data-loaded","true");return}const s=Array.from(i.children);let o=[],r=null;switch(e){case"staff-picks":o=s.filter(a=>a.getAttribute("data-staff-pick")==="1");break;case"most-read":r="/search?sort=views";break;case"africa-section":r="/africa";break;case"politics-society":r="/politics-society";break;case"timelines":r="/timelines";break;default:o=s}if(r){const a=e.replace(/-/g," ").replace(/\b\w/g,c=>c.toUpperCase());t.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="saho-category-redirect p-4 rounded shadow-sm bg-white">
            <i class="fas fa-external-link-alt fa-2x mb-3 saho-text-primary"></i>
            <h4>Explore ${a}</h4>
            <p class="text-muted mb-3">Visit our dedicated ${a} section for more content.</p>
            <a href="${r}" class="btn saho-bg-primary text-white px-4 py-2">
              <i class="fas fa-arrow-right me-2"></i>Go to ${a}
            </a>
          </div>
        </div>
      `,t.setAttribute("data-loaded","true");return}o.length>0?(t.innerHTML="",o.forEach(a=>{const c=a.cloneNode(!0);t.appendChild(c)})):t.innerHTML=`
        <div class="col-12 text-center py-5">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No ${e.replace(/-/g," ").replace(/\b\w/g,a=>a.toUpperCase())} items found in the current featured content.
          </div>
        </div>
      `,t.setAttribute("data-loaded","true")},n.prototype.announceChange=e=>{const t=document.createElement("div");t.setAttribute("aria-live","polite"),t.setAttribute("aria-atomic","true"),t.className="visually-hidden",t.textContent=e,document.body.appendChild(t),setTimeout(()=>{document.body.removeChild(t)},1e3)},window.SahoFeaturedGrid=n})(Drupal,once);
