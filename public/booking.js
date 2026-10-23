'use strict';
const form=document.getElementById('booking-form');
if(form){
 const branch=form.elements.branch_id,staff=form.elements.employee_id;
 const staffBranches=Array.from(staff.options).map(o=>({option:o,branch:o.textContent.split(' · ').pop()}));
 // The server validates staff-to-branch mapping and every slot again on submission.
 document.getElementById('check-slots').addEventListener('click',async()=>{
  const status=document.getElementById('slot-status'),list=document.getElementById('available-slots');list.replaceChildren(new Option('Choose an available time',''));
  const date=form.elements.starts_at.value.slice(0,10);if(!date){status.textContent='Choose a date in the date & time field first.';return;}
  status.textContent='Checking availability…';const params=new URLSearchParams({branch_id:branch.value,service_id:form.elements.service_id.value,employee_id:staff.value,date});
  try{const response=await fetch(form.dataset.availability+'?'+params.toString(),{headers:{Accept:'application/json'}});if(!response.ok)throw new Error('Choose a stylist at the selected branch and a date within the next three months.');const slots=await response.json();slots.forEach(slot=>list.add(new Option(slot.label,slot.value)));status.textContent=slots.length?'Select a time below. Availability is checked again when booking.':'No times available. Try another date or stylist.';}catch(error){status.textContent=error.message;}
 });
 document.getElementById('available-slots').addEventListener('change',event=>{if(event.target.value)form.elements.starts_at.value=event.target.value;});
}
