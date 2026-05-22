                            required>
                            <small class="text-muted">We only store the last 4 digits for security</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="card_holder" class="form-label">Card Holder Name *</label>
                                <input type="text" class="form-control" id="card_holder" name="card_holder" 
                                       value="<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiration Date *</label>
                                <div class="row">
                                    <div class="col-6">
                                        <select class="form-select" id="expiry_month" name="expiry_month" required>
                                            <option value="">Month</option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select" id="expiry_year" name="expiry_year" required>
                                            <option value="">Year</option>
                                            <?php 
                                            $current_year = date('Y');
                                            for ($i = 0; $i < 10; $i++): ?>
                                            <option value="<?php echo $current_year + $i; ?>">
                                                <?php echo $current_year + $i; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" checked>
                                <label class="form-check-label" for="is_default">
                                    Set as default payment method
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_card" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Card
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Card Modal -->
    <div class="modal fade" id="editCardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Card</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="edit-card-form">
                    <input type="hidden" id="edit_card_id" name="card_id">
                    <div class="modal-body">
                        <div id="edit-card-loading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading card details...</p>
                        </div>
                        <div id="edit-card-content" style="display: none;">
                            <div class="mb-3">
                                <label for="edit_card_type" class="form-label">Card Type *</label>
                                <select class="form-select" id="edit_card_type" name="card_type" required>
                                    <option value="">Select Card Type</option>
                                    <option value="visa">Visa</option>
                                    <option value="mastercard">Mastercard</option>
                                    <option value="amex">American Express</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" value="•••• •••• •••• XXXX" disabled>
                                <small class="text-muted">For security, full card number cannot be displayed</small>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="edit_card_holder" class="form-label">Card Holder Name *</label>
                                    <input type="text" class="form-control" id="edit_card_holder" name="card_holder" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiration Date *</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <select class="form-select" id="edit_expiry_month" name="expiry_month" required>
                                                <option value="">Month</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                    <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select" id="edit_expiry_year" name="expiry_year" required>
                                                <option value="">Year</option>
                                                <?php 
                                                $current_year = date('Y');
                                                for ($i = 0; $i < 10; $i++): ?>
                                                <option value="<?php echo $current_year + $i; ?>">
                                                    <?php echo $current_year + $i; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default">
                                    <label class="form-check-label" for="edit_is_default">
                                        Set as default payment method
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_card" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Card
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            // Add spaces every 4 digits
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            
            if (value.length > 19) { // 16 digits + 3 spaces
                value = value.substring(0, 19);
            }
            
            this.value = value;
        });
        
        // Load edit card data
        function loadEditCard(cardId) {
            const loadingDiv = document.getElementById('edit-card-loading');
            const contentDiv = document.getElementById('edit-card-content');
            
            // Show loading, hide content
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Fetch card data
            fetch(`../api/payment.php?action=get_card&id=${cardId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('edit_card_id').value = data.card.id;
                        document.getElementById('edit_card_type').value = data.card.card_type;
                        document.getElementById('edit_card_holder').value = data.card.card_holder;
                        document.getElementById('edit_expiry_month').value = data.card.expiry_month;
                        document.getElementById('edit_expiry_year').value = data.card.expiry_year;
                        document.getElementById('edit_is_default').checked = data.card.is_default;
                        
                        // Hide loading, show content
                        loadingDiv.style.display = 'none';
                        contentDiv.style.display = 'block';
                    } else {
                        alert('Failed to load card details.');
                        $('#editCardModal').modal('hide');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading card details.');
                    $('#editCardModal').modal('hide');
                });
        }
        
        // Reset edit modal when hidden
        $('#editCardModal').on('hidden.bs.modal', function() {
            const loadingDiv = document.getElementById('edit-card-loading');
            const contentDiv = document.getElementById('edit-card-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
        });
    </script>
</body>
</html>