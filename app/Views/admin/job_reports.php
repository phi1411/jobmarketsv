<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div class="detail-card" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem;">
        <div>
            <h2 style="margin:0 0 .25rem;font-size:1.35rem;">Hàng Đợi Báo Cáo Tin</h2>
            <p style="margin:0;color:var(--text-muted);">Xem xét phản ánh của sinh viên và xử lý tin vi phạm.</p>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <select id="report-status" class="form-control" onchange="loadReports(1)">
                <option value="">Tất cả trạng thái</option><option value="pending">Chờ xử lý</option><option value="reviewing">Đang xem xét</option><option value="resolved">Đã xử lý</option><option value="dismissed">Không vi phạm</option>
            </select>
            <select id="report-reason" class="form-control" onchange="loadReports(1)">
                <option value="">Tất cả lý do</option><option value="scam">Nghi lừa đảo</option><option value="salary_mismatch">Sai thông tin lương</option><option value="fee_required">Yêu cầu đóng phí</option><option value="inappropriate">Nội dung không phù hợp</option><option value="other">Lý do khác</option>
            </select>
        </div>
    </div>
    <div id="report-summary" style="color:var(--text-muted);margin-bottom:1rem;">Đang tải...</div>
    <div id="report-list" style="display:grid;gap:1rem;"></div>
</div>

<div id="report-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="detail-card" style="width:min(560px,100%);margin:0;max-height:90vh;overflow:auto;">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;">Xử lý báo cáo</h3><button class="modal-close-btn" onclick="closeReportModal()">&times;</button>
        </div>
        <div id="report-modal-info" style="padding:1rem;background:#f8fafc;border-radius:var(--radius-sm);margin-bottom:1rem;"></div>
        <form onsubmit="saveReport(event)">
            <input type="hidden" id="report-id">
            <div class="form-group"><label class="form-label">Kết quả</label><select id="resolution-status" class="form-control" required><option value="reviewing">Đang xem xét</option><option value="resolved">Đã xử lý</option><option value="dismissed">Không ghi nhận vi phạm</option></select></div>
            <div class="form-group"><label class="form-label">Hành động với tin</label><select id="resolution-action" class="form-control"><option value="none">Không thay đổi tin</option><option value="hide_job">Ẩn tin khỏi sàn</option><option value="close_job">Đóng tin</option></select><small class="form-help">Ẩn/đóng tin chỉ áp dụng khi chọn “Đã xử lý”.</small></div>
            <div class="form-group"><label class="form-label">Ghi chú xử lý</label><textarea id="resolution-note" class="form-control" rows="4" maxlength="1000" placeholder="Căn cứ xử lý hoặc lý do bác bỏ báo cáo..."></textarea></div>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;"><button type="button" class="btn btn-outline" onclick="closeReportModal()">Hủy</button><button id="save-report-btn" class="btn btn-primary">Lưu kết quả</button></div>
        </form>
    </div>
</div>

<script>
let reportRows = [];
const reasonLabels = {scam:"Nghi lừa đảo",salary_mismatch:"Sai thông tin lương",fee_required:"Yêu cầu đóng phí",inappropriate:"Nội dung không phù hợp",other:"Lý do khác"};
const statusLabels = {pending:"Chờ xử lý",reviewing:"Đang xem xét",resolved:"Đã xử lý",dismissed:"Không vi phạm"};
document.addEventListener("DOMContentLoaded", () => {
    const user = TokenStorage.getUser();
    if (!TokenStorage.isLoggedIn() || !user || user.role !== "admin") { window.location.href = "/login?login_required=1&redirect=/admin/job-reports"; return; }
    loadReports(1);
});
async function loadReports(page) {
    const params = new URLSearchParams({page, per_page:10});
    const status = document.getElementById("report-status").value, reason = document.getElementById("report-reason").value;
    if(status) params.set("status",status); if(reason) params.set("reason",reason);
    const res = await apiRequest(`/admin/job-reports?${params}`, {requireAuth:true});
    const list = document.getElementById("report-list");
    if(!res || !res.success){ list.innerHTML = `<div class="detail-card">Không thể tải báo cáo.</div>`; return; }
    reportRows = res.data || []; document.getElementById("report-summary").innerText = `${res.meta?.total ?? reportRows.length} báo cáo`;
    if(!reportRows.length){ list.innerHTML = `<div class="detail-card" style="text-align:center;padding:3rem;"><div style="font-size:2.5rem">✓</div><h3>Không có báo cáo phù hợp</h3><p style="color:var(--text-muted)">Hàng đợi hiện đã trống.</p></div>`; return; }
    list.innerHTML = reportRows.map(r => `<article class="detail-card" style="margin:0;border-left:4px solid ${r.status==='pending'?'#f59e0b':r.status==='reviewing'?'#3b82f6':r.status==='resolved'?'#10b981':'#94a3b8'}">
      <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap"><div><h3 style="margin:0 0 .35rem"><a href="/viec-lam/${encodeURIComponent(r.job_id)}" target="_blank">${escapeHtml(r.job_title)}</a></h3><div style="color:var(--text-muted);font-size:.88rem">${escapeHtml(r.company_name)} · Người báo: ${escapeHtml(r.reporter_name)} · ${formatDate(r.created_at)}</div></div><span class="badge">${escapeHtml(statusLabels[r.status]||r.status)}</span></div>
      <div style="margin-top:1rem"><strong>${escapeHtml(reasonLabels[r.reason]||r.reason)}</strong>${r.description?`<p style="margin:.45rem 0 0">${escapeHtml(r.description)}</p>`:''}</div>
      ${r.admin_note?`<div style="margin-top:.75rem;padding:.75rem;background:#f8fafc;border-radius:8px"><strong>Ghi chú admin:</strong> ${escapeHtml(r.admin_note)}</div>`:''}
      <div style="margin-top:1rem;text-align:right"><button class="btn btn-primary btn-sm" onclick="openReportModal('${escapeHtml(r.id)}')">Xem xét / Xử lý</button></div></article>`).join('');
}
function openReportModal(id){ const r=reportRows.find(x=>x.id===id); if(!r)return; document.getElementById('report-id').value=id; document.getElementById('resolution-status').value=r.status==='pending'?'reviewing':r.status; document.getElementById('resolution-action').value=r.resolution_action||'none'; document.getElementById('resolution-note').value=r.admin_note||''; document.getElementById('report-modal-info').innerHTML=`<strong>${escapeHtml(r.job_title)}</strong><br>${escapeHtml(reasonLabels[r.reason]||r.reason)}${r.description?`<br>${escapeHtml(r.description)}`:''}`; document.getElementById('report-modal').style.display='flex'; }
function closeReportModal(){document.getElementById('report-modal').style.display='none';}
async function saveReport(e){e.preventDefault(); const btn=document.getElementById('save-report-btn'); btn.disabled=true; const id=document.getElementById('report-id').value; const res=await apiRequest(`/admin/job-reports/${encodeURIComponent(id)}`,{method:'PATCH',requireAuth:true,body:{status:document.getElementById('resolution-status').value,resolution_action:document.getElementById('resolution-action').value,admin_note:document.getElementById('resolution-note').value.trim()}}); btn.disabled=false; if(res?.success){showToast('Đã lưu kết quả xử lý.','success');closeReportModal();loadReports(1);}else showToast(res?.message||'Không thể xử lý báo cáo.','error');}
</script>
