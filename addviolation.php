<style>
    /* Header styled with primary blue */
    .modal-header { 
        background-color: #0059b3; 
        padding: 20px 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        color: #ffffff; 
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    .modal-content { padding: 0 !important; overflow: hidden; }
    .modal-body { padding: 30px; }
</style>

<div id="violationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-circle-plus"></i> New Violation</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="violations.php" method="POST">
                
                <div class="form-group">
                    <label>Driver</label>
                    <select name="driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php
                        // Fetching from 'drivers' table
                        $drivers_query = $conn->query("SELECT driver_id, full_name FROM drivers ORDER BY full_name ASC");
                        while($d = $drivers_query->fetch_assoc()) {
                            echo "<option value='{$d['driver_id']}'>{$d['full_name']} ({$d['driver_id']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle (Plate No.)</label>
                    <select name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php
                        // Fetching from 'vehicles' table
                        $vehicles_query = $conn->query("SELECT vehicle_id, plate_no FROM vehicles ORDER BY plate_no ASC");
                        while($v = $vehicles_query->fetch_assoc()) {
                            echo "<option value='{$v['vehicle_id']}'>{$v['plate_no']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Violation Type</label>
                    <select name="violation_type" required>
                        <option value="">-- Select Violation --</option>
                        <?php
                        // Dynamic fetch from violation_types table
                        $types_query = $conn->query("SELECT violation_name FROM violation_types ORDER BY violation_name ASC");
                        if($types_query && $types_query->num_rows > 0) {
                            while($t = $types_query->fetch_assoc()) {
                                echo "<option value='{$t['violation_name']}'>{$t['violation_name']}</option>";
                            }
                        } else {
                            // Fallback if table is empty
                            echo "<option value='Speeding'>Speeding</option>";
                            echo "<option value='Reckless Driving'>Reckless Driving</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" name="violation_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>

                <button type="submit" name="save_violation" class="btn-save">Save Record</button>
            </form>
        </div>
    </div>
</div>