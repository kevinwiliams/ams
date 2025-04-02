<!-- Hidden printable form -->
<div id="printEquipmentForm" style="display:none;">
    <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
        <!-- Header with logo and title -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <div>
                <h4 style="margin: 0; color: #2c3e50;">
                    <i class="fas fa-tools" style="margin-right: 10px;"></i>
                    Outside Broadcast Equipment Gate Pass
                </h4>
                <p style="margin: 5px 0 0; color: #7f8c8d; font-size: 14px;">
                    <i class="fas fa-calendar-alt" style="margin-right: 5px;"></i>
                    Generated on: <span id="print-current-date"></span>
                </p>
            </div>
            <div style="text-align: right;">
                <!-- You can add your company logo here if needed -->
                <!-- <img src="logo.png" style="height: 50px;"> -->
            </div>
        </div>

        <!-- Assignment details card -->
        <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #3498db;">
            <div style="display: flex; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                    <p style="margin: 0; font-weight: bold; color: #3498db;">
                        <i class="fas fa-heading" style="margin-right: 8px;"></i>
                        Assignment
                    </p>
                    <p style="margin: 5px 0 0 23px;" id="print-assignment-title"></p>
                </div>
                <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                    <p style="margin: 0; font-weight: bold; color: #3498db;">
                        <i class="fas fa-calendar-day" style="margin-right: 8px;"></i>
                        Date
                    </p>
                    <p style="margin: 5px 0 0 23px;" id="print-assignment-date"></p>
                </div>
                <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                    <p style="margin: 0; font-weight: bold; color: #3498db;">
                        <i class="fas fa-clock" style="margin-right: 8px;"></i>
                        Time
                    </p>
                    <p style="margin: 5px 0 0 23px;" id="print-assignment-time"></p>
                </div>
            </div>
            <div style="display: none; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <p style="margin: 0; font-weight: bold; color: #3498db;">
                        <i class="fas fa-map-marked-alt" style="margin-right: 8px;"></i>
                        Site Visit Date
                    </p>
                    <p style="margin: 5px 0 0 23px;" id="print-site-visit-date"></p>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <p style="margin: 0; font-weight: bold; color: #3498db;">
                        <i class="fas fa-stopwatch" style="margin-right: 8px;"></i>
                        Setup Time
                    </p>
                    <p style="margin: 5px 0 0 23px;" id="print-setup-time"></p>
                </div>
            </div>
        </div>

        <!-- Equipment table card -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
            <div style="background: #2c3e50; color: white; padding: 10px 15px; display: none;">
                <h3 style="margin: 0; font-size: 16px;">
                    <i class="fas fa-clipboard-list" style="margin-right: 10px;"></i>
                    Equipment Inventory
                </h3>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #ddd;">Equipment</th>
                        <th style="padding: 12px 15px; text-align: center; border-bottom: 2px solid #ddd; width: 100px;">Qty OUT</th>
                        <th style="padding: 12px 15px; text-align: center; border-bottom: 2px solid #ddd; width: 100px;">Qty IN</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #ddd;">Notes</th>
                    </tr>
                </thead>
                <tbody id="print-equipment-body" style="font-size: 14px;">
                    <!-- Will be filled by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Signature cards -->
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
            <!-- Security Sign In/Out -->
            <div style="flex: 1; min-width: 300px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px;">
                <div style="background: #e74c3c; color: white; padding: 8px 15px; margin: -20px -20px 15px -20px; border-radius: 8px 8px 0 0;">
                    <h3 style="margin: 0; font-size: 15px;">
                        <i class="fas fa-user-shield" style="margin-right: 8px;"></i>
                        Security Department
                    </h3>
                </div>
                
                <!-- Sign In Section -->
                <div style="margin-bottom: 30px;">
                    <h4 style="margin: 0 0 10px 0; color: #e74c3c; font-size: 14px;">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                        Equipment Check-Out
                    </h4>
                    <div style="margin: 15px 0;">
                        <!-- <p style="margin-bottom: 40px;">Signature: _________________________</p> -->
                        <p>Sign/Print: _________________________</p>
                        <p>Date: ___________ Time: ___________</p>
                    </div>
                </div>
                
                <!-- Sign Out Section -->
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #e74c3c; font-size: 14px;">
                        <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>
                        Equipment Check-In
                    </h4>
                    <div style="margin: 15px 0;">
                        <!-- <p style="margin-bottom: 40px;">Signature: _________________________</p> -->
                        <p>Sign/Print: _________________________</p>
                        <p>Date: ___________ Time: ___________</p>
                    </div>
                </div>
            </div>
            
            <!-- Technician Sign In/Out -->
            <div style="flex: 1; min-width: 300px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px;">
                <div style="background: #27ae60; color: white; padding: 8px 15px; margin: -20px -20px 15px -20px; border-radius: 8px 8px 0 0;">
                    <h3 style="margin: 0; font-size: 15px;">
                        <i class="fas fa-tv" style="margin-right: 8px;"></i>
                        Broadcast Technician
                    </h3>
                </div>
                
                <!-- Sign In Section -->
                <div style="margin-bottom: 30px;">
                    <h4 style="margin: 0 0 10px 0; color: #27ae60; font-size: 14px;">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                        Equipment Check-Out
                    </h4>
                    <div style="margin: 15px 0;">
                        <!-- <p style="margin-bottom: 40px;">Signature: _________________________</p> -->
                        <p>Sign/Print: _________________________</p>
                        <p>Date: ___________ Time: ___________</p>
                    </div>
                </div>
                
                <!-- Sign Out Section -->
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #27ae60; font-size: 14px;">
                        <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>
                        Equipment Check-In
                    </h4>
                    <div style="margin: 15px 0;">
                        <!-- <p style="margin-bottom: 40px;">Signature: _________________________</p> -->
                        <p>Sign/Print: _________________________</p>
                        <p>Date: ___________ Time: ___________</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #95a5a6; font-size: 12px;">
            <p>This is an automatically generated equipment form. Please verify all items before signing.</p>
        </div>
    </div>
</div>